<?php

namespace Modules\Broadcast\Jobs;

use App\Mail\ReplyToCustomer;
use App\Misc\SwiftGetSmtpQueueId;
use App\SendLog;
use App\Thread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;
use Modules\Broadcast\Entities\BroadcastConversation;
use Modules\Broadcast\Entities\BroadcastSend;

/**
 * Sends one individually-addressed copy of a broadcast conversation's message
 * to every member of its recipient list, in place of FreeScout's normal
 * single-recipient customer reply job (which this module suppresses via the
 * `conversation.skip_send_reply_to_customer` filter for these conversations).
 *
 * Each recipient only ever sees their own address - nobody else's - and gets
 * a Message-ID minted by BroadcastSend::generateMessageId() rather than
 * FreeScout's own thread Message-ID, so that if they reply, FreeScout's
 * reply-matching in FetchEmails will not find this thread and will create a
 * fresh conversation instead of appending to this broadcast.
 */
class SendBroadcastEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $timeout = 300;

    protected $conversationId;
    protected $threadIds;

    public function __construct($conversation, $threads)
    {
        $this->conversationId = $conversation->id;
        $this->threadIds = $threads->pluck('id')->all();
    }

    public function handle()
    {
        $conversation = \App\Conversation::find($this->conversationId);
        if (!$conversation) {
            return;
        }

        $mailbox = $conversation->mailbox;
        if (!$mailbox) {
            return;
        }

        $broadcastConversation = BroadcastConversation::where('conversation_id', $conversation->id)->first();
        if (!$broadcastConversation) {
            return;
        }

        $threads = Thread::whereIn('id', $this->threadIds)->get();
        $threads = Thread::sortThreads($threads);
        if ($threads->isEmpty()) {
            return;
        }
        $thread = $threads->first();

        \MailHelper::setMailDriver($mailbox, $thread->created_by_user, $conversation);

        if (!\MailHelper::$smtp_queue_id_plugin_registered) {
            \Mail::getSwiftMailer()->registerPlugin(new SwiftGetSmtpQueueId());
            \MailHelper::$smtp_queue_id_plugin_registered = true;
        }

        $subject = $conversation->subject;
        $domain = $mailbox->getEmailDomain();

        $sends = $broadcastConversation->sends()->where('status', BroadcastSend::STATUS_PENDING)->get();

        foreach ($sends as $send) {
            $message_id = $send->generateMessageId($domain);
            $send->message_id = $message_id;

            $headers = [
                'Message-ID' => $message_id,
                'X-FreeScout-Mail-Type' => 'customer.message',
            ];

            $reply_mail = new ReplyToCustomer($conversation, $threads, $headers, $mailbox, $subject, $threads->count());

            try {
                Mail::to([['name' => $send->name, 'email' => $send->email]])->send($reply_mail);

                $send->status = BroadcastSend::STATUS_SENT;
                $send->sent_at = now();
                $send->error = null;
            } catch (\Exception $e) {
                $send->status = BroadcastSend::STATUS_FAILED;
                $send->error = $e->getMessage();

                activity()
                    ->withProperties([
                        'error' => $e->getMessage().'; File: '.$e->getFile().' ('.$e->getLine().')',
                        'to' => $send->email,
                        'conversation' => '#'.$conversation->number,
                    ])
                    ->useLog(\App\ActivityLog::NAME_EMAILS_SENDING)
                    ->log(\App\ActivityLog::DESCRIPTION_EMAILS_SENDING_ERROR_TO_CUSTOMER);
            }

            $send->save();
        }

        $thread->send_status = SendLog::STATUS_ACCEPTED;
        $thread->save();
    }
}
