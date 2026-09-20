<?php

namespace Modules\Broadcast\Http\Controllers;

use App\Mailbox;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Broadcast\Entities\BroadcastList;
use Modules\Broadcast\Entities\BroadcastListMember;
use Modules\Broadcast\Entities\BroadcastListPermission;

class BroadcastListsController extends Controller
{
    public function index()
    {
        $lists = BroadcastList::withCount('members')->editableBy(Auth::user())->orderBy('name')->get();

        return view('broadcast::lists.index', compact('lists'));
    }

    public function create()
    {
        $this->authorizeAdmin();

        $mailboxes = Mailbox::all();

        return view('broadcast::lists.create', compact('mailboxes'));
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin();

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
        $this->authorizeEdit($list);

        $mailboxes = Mailbox::all();
        $members = $list->members()->orderBy('email')->get();

        $is_admin = Auth::user()->isAdmin();
        // Admins already have full access to every list, so only non-admins
        // are meaningful entries in the permissions grid.
        $users = $is_admin ? User::nonDeleted()->orderBy('first_name')->get()->reject->isAdmin() : collect();
        $permissions = $is_admin ? $list->permissions->keyBy('user_id') : collect();

        return view('broadcast::lists.edit', compact('list', 'mailboxes', 'members', 'users', 'permissions', 'is_admin'));
    }

    public function update(Request $request, $id)
    {
        $list = BroadcastList::findOrFail($id);
        $this->authorizeEdit($list);

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

        // Granting/revoking access is itself an admin-only privilege, so a
        // non-admin editor's request simply has no "permissions_submitted"
        // marker. The marker (rather than checking for "permissions" itself)
        // is needed because unchecking every checkbox omits the array
        // entirely from the request.
        if (Auth::user()->isAdmin() && $request->has('permissions_submitted')) {
            $this->syncPermissions($list, $request->input('permissions', []));
        }

        \Session::flash('flash_success_floating', __('Recipient list saved'));

        return redirect()->route('broadcast.lists.edit', ['id' => $list->id]);
    }

    public function destroy($id)
    {
        $this->authorizeAdmin();

        $list = BroadcastList::findOrFail($id);
        $list->delete();

        \Session::flash('flash_success_floating', __('Recipient list deleted'));

        return redirect()->route('broadcast.lists');
    }

    protected function authorizeAdmin()
    {
        if (!Auth::user() || !Auth::user()->isAdmin()) {
            abort(403);
        }
    }

    protected function authorizeEdit(BroadcastList $list)
    {
        if (!Auth::user() || !$list->userCanEdit(Auth::user())) {
            abort(403);
        }
    }

    /**
     * Replace a list's permission grants from the edit form's
     * permissions[user_id][edit|use] checkboxes.
     */
    protected function syncPermissions(BroadcastList $list, array $permissions)
    {
        DB::transaction(function () use ($list, $permissions) {
            $list->permissions()->delete();

            foreach ($permissions as $user_id => $grant) {
                $can_edit = !empty($grant['edit']);
                $can_use = !empty($grant['use']);

                if (!$can_edit && !$can_use) {
                    continue;
                }

                BroadcastListPermission::create([
                    'broadcast_list_id' => $list->id,
                    'user_id' => (int) $user_id,
                    'can_edit' => $can_edit,
                    'can_use' => $can_use,
                ]);
            }
        });
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
