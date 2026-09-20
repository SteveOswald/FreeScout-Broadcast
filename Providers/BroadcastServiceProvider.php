<?php

namespace Modules\Broadcast\Providers;

use App\Conversation;
use Illuminate\Support\ServiceProvider;
use Modules\Broadcast\Entities\BroadcastConversation;
use Modules\Broadcast\Entities\BroadcastList;
use Modules\Broadcast\Entities\BroadcastSend;
use Modules\Broadcast\Jobs\SendBroadcastEmails;

define('BROADCAST_MODULE', 'broadcast');

class BroadcastServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function boot()
    {
        $this->registerTranslations();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->registerMenuHook();
        $this->registerComposeFormHook();
        $this->registerSendReplySaveHook();
        $this->registerSkipSendHook();
        $this->registerFetchEmailsSafetyNet();
    }

    public function register()
    {
        //
    }

    public function registerViews()
    {
        $viewPath = resource_path('views/modules/broadcast');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path.'/modules/broadcast';
        }, \Config::get('view.paths')), [$sourcePath]), 'broadcast');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/broadcast');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'broadcast');
        } else {
            $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'broadcast');
        }
    }

    /**
     * Add "Recipient Lists" to the admin "Manage" menu.
     */
    protected function registerMenuHook()
    {
        \Eventy::addAction('menu.manage.append', function () {
            echo view('broadcast::partials.menu')->render();
        }, 20, 0);
    }

    /**
     * Inject the recipient-list picker into the "New Conversation" compose
     * form, right above the Subject field.
     */
    protected function registerComposeFormHook()
    {
        \Eventy::addAction('conversation.create_form.before_subject', function ($conversation, $mailbox, $thread) {
            $lists = BroadcastList::withCount('members')
                ->where(function ($query) use ($mailbox) {
                    $query->whereNull('mailbox_id');
                    if ($mailbox) {
                        $query->orWhere('mailbox_id', $mailbox->id);
                    }
                })
                ->orderBy('name')
                ->get();

            echo view('broadcast::partials.list_picker', ['lists' => $lists])->render();
        }, 20, 3);
    }

    /**
     * Right after a new conversation is saved, if the agent picked a
     * recipient list, record the association and queue up one pending
     * BroadcastSend per list member. This runs before the
     * UserCreatedConversation event (and therefore before the
     * conversation.skip_send_reply_to_customer filter below), so the
     * mapping is already in place by the time sending is decided.
     */
    protected function registerSendReplySaveHook()
    {
        \Eventy::addAction('conversation.send_reply_save', function ($conversation, $request) {
            $list_id = $request->input('broadcast_list_id');

            if (empty($list_id) || !is_numeric($list_id)) {
                return;
            }

            $list = BroadcastList::with('members')->find($list_id);
            if (!$list || $list->members->isEmpty()) {
                return;
            }

            // A list is BCC-style by nature: make sure no CC/BCC entered in
            // the compose form (e.g. before the list was picked) survives,
            // as that would let recipients see each other's address.
            if ($conversation->cc || $conversation->bcc) {
                $conversation->setCc([]);
                $conversation->setBcc([]);
                $conversation->save();
            }

            $broadcast_conversation = BroadcastConversation::updateOrCreate(
                ['conversation_id' => $conversation->id],
                ['broadcast_list_id' => $list->id]
            );

            foreach ($list->members as $member) {
                BroadcastSend::updateOrCreate(
                    [
                        'broadcast_conversation_id' => $broadcast_conversation->id,
                        'email' => $member->email,
                    ],
                    [
                        'name' => $member->name,
                        'status' => BroadcastSend::STATUS_PENDING,
                    ]
                );
            }
        }, 20, 2);
    }

    /**
     * Suppress FreeScout's normal single-recipient customer email for
     * broadcast conversations, and queue our own per-recipient sends
     * instead so that only the module's mailer loop ever emails the real
     * recipients.
     */
    protected function registerSkipSendHook()
    {
        \Eventy::addFilter('conversation.skip_send_reply_to_customer', function ($skip, $conversation, $replies) {
            if ($skip) {
                return $skip;
            }

            $broadcast_conversation = BroadcastConversation::where('conversation_id', $conversation->id)->first();
            if (!$broadcast_conversation) {
                return $skip;
            }

            $delay = \Eventy::filter('conversation.send_reply_to_customer_delay', now()->addSeconds(Conversation::UNDO_TIMOUT), $conversation, $replies);

            SendBroadcastEmails::dispatch($conversation, $replies)
                ->delay($delay)
                ->onQueue('emails');

            return true;
        }, 20, 3);
    }

    /**
     * Defensive safety net: FreeScout's own reply-matching regexes only
     * recognize its "FS_reply"/"FS_notify"/"FS_autoreply" Message-ID
     * prefixes, so replies to our "FS_broadcast"-prefixed Message-IDs never
     * match an existing thread and a new conversation is created
     * automatically. This filter double-checks that explicitly, in case a
     * future core change widens that matching.
     */
    protected function registerFetchEmailsSafetyNet()
    {
        \Eventy::addFilter('fetch_emails.data_to_save', function ($data) {
            if (empty($data['prev_thread']) || empty($data['message'])) {
                return $data;
            }

            $message = $data['message'];
            $candidates = [];

            $in_reply_to = trim($message->getInReplyTo() ?? '', '<>');
            if ($in_reply_to) {
                $candidates[] = $in_reply_to;
            }

            $references = $message->getReferences();
            if ($references && !is_array($references)) {
                $references = array_filter(preg_split('/[, <>]/', $references));
            }
            if ($references) {
                foreach ($references as $reference) {
                    $reference = trim($reference, '<> ');
                    if ($reference) {
                        $candidates[] = $reference;
                    }
                }
            }

            foreach ($candidates as $candidate) {
                if (BroadcastSend::idFromMessageId($candidate)) {
                    $data['prev_thread'] = null;
                    break;
                }
            }

            return $data;
        }, 20, 1);
    }

    public function provides()
    {
        return [];
    }
}
