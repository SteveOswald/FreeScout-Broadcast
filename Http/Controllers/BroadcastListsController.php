<?php

namespace Modules\Broadcast\Http\Controllers;

use App\Mailbox;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Broadcast\Entities\BroadcastList;
use Modules\Broadcast\Entities\BroadcastListMember;

class BroadcastListsController extends Controller
{
    public function index()
    {
        $lists = BroadcastList::withCount('members')->orderBy('name')->get();

        return view('broadcast::lists.index', compact('lists'));
    }

    public function create()
    {
        $mailboxes = Mailbox::all();

        return view('broadcast::lists.create', compact('mailboxes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'mailbox_id' => 'nullable|integer',
            'members_raw' => 'nullable|string',
        ]);

        $list = new BroadcastList();
        $list->name = $request->name;
        $list->description = $request->description;
        $list->mailbox_id = $request->mailbox_id ?: null;
        $list->virtual_email = BroadcastList::generateVirtualEmail();
        $list->created_by_user_id = Auth::id();
        $list->save();

        $this->syncMembers($list, $request->members_raw);

        \Session::flash('flash_success_floating', __('Recipient list created'));

        return redirect()->route('broadcast.lists.edit', ['id' => $list->id]);
    }

    public function edit($id)
    {
        $list = BroadcastList::findOrFail($id);
        $mailboxes = Mailbox::all();
        $members = $list->members()->orderBy('email')->get();

        return view('broadcast::lists.edit', compact('list', 'mailboxes', 'members'));
    }

    public function update(Request $request, $id)
    {
        $list = BroadcastList::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string',
            'mailbox_id' => 'nullable|integer',
            'members_raw' => 'nullable|string',
        ]);

        $list->name = $request->name;
        $list->description = $request->description;
        $list->mailbox_id = $request->mailbox_id ?: null;
        $list->save();

        // Keep the virtual customer's display name in sync with the list name.
        $list->getOrCreateVirtualCustomer();

        if ($request->has('members_raw')) {
            $this->syncMembers($list, $request->members_raw);
        }

        \Session::flash('flash_success_floating', __('Recipient list saved'));

        return redirect()->route('broadcast.lists.edit', ['id' => $list->id]);
    }

    public function destroy($id)
    {
        $list = BroadcastList::findOrFail($id);
        $list->delete();

        \Session::flash('flash_success_floating', __('Recipient list deleted'));

        return redirect()->route('broadcast.lists');
    }

    /**
     * Replace a list's full member set from a textarea of one address per
     * line (optionally "Name <email@example.com>"), comma-separated lines
     * also accepted. Returns nothing; invalid lines are simply skipped.
     */
    protected function syncMembers(BroadcastList $list, $raw)
    {
        $parsed = [];

        $lines = preg_split('/[\r\n,]+/', $raw ?? '');

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $name = null;
            $email = $line;

            if (preg_match('/^(.*?)<([^<>]+)>$/', $line, $m)) {
                $name = trim($m[1], " \t\"'");
                $email = trim($m[2]);
            }

            $email = mb_strtolower($email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $parsed[$email] = $name ?: null;
        }

        DB::transaction(function () use ($list, $parsed) {
            $list->members()->delete();

            foreach ($parsed as $email => $name) {
                BroadcastListMember::create([
                    'broadcast_list_id' => $list->id,
                    'email' => $email,
                    'name' => $name,
                ]);
            }
        });
    }
}
