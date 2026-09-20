{{ csrf_field() }}
@if (!empty($list->id))
    {{ method_field('PUT') }}
@endif

<div class="form-group{{ $errors->has('name') ? ' has-error' : '' }}">
    <label for="name" class="col-sm-3 control-label">{{ __('List Name') }}</label>

    <div class="col-sm-8">
        <input id="name" type="text" class="form-control" name="name" value="{{ old('name', $list->name ?? '') }}" maxlength="191" required autofocus>
        @include('partials/field_error', ['field' => 'name'])
    </div>
</div>

<div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
    <label for="description" class="col-sm-3 control-label">{{ __('Description') }}</label>

    <div class="col-sm-8">
        <input id="description" type="text" class="form-control" name="description" value="{{ old('description', $list->description ?? '') }}" maxlength="191">
        @include('partials/field_error', ['field' => 'description'])
    </div>
</div>

<div class="form-group{{ $errors->has('mailbox_id') ? ' has-error' : '' }}">
    <label for="mailbox_id" class="col-sm-3 control-label">{{ __('Mailbox') }}</label>

    <div class="col-sm-8">
        <select id="mailbox_id" class="form-control" name="mailbox_id">
            <option value="">{{ __('Any mailbox') }}</option>
            @foreach ($mailboxes as $mailbox_item)
                <option value="{{ $mailbox_item->id }}" @if ((int) old('mailbox_id', $list->mailbox_id ?? '') === $mailbox_item->id) selected @endif>{{ $mailbox_item->name }}</option>
            @endforeach
        </select>
        <p class="help-block">{{ __('Restrict this list so it can only be selected when composing from a specific mailbox. Leave as "Any mailbox" to make it available everywhere.') }}</p>
        @include('partials/field_error', ['field' => 'mailbox_id'])
    </div>
</div>

<div class="form-group{{ $errors->has('members_raw') ? ' has-error' : '' }}">
    <label for="members_raw" class="col-sm-3 control-label">{{ __('Recipients') }}</label>

    <div class="col-sm-8">
        <textarea id="members_raw" class="form-control" name="members_raw" rows="12" placeholder="jane@example.com&#10;John Doe &lt;john@example.com&gt;">{{ old('members_raw', isset($members) ? $members->map(function ($m) { return $m->name ? $m->name.' <'.$m->email.'>' : $m->email; })->implode("\n") : '') }}</textarea>
        <p class="help-block">{{ __('One recipient per line, either just the email address or "Name <email@example.com>". Saving replaces the entire recipient list.') }}</p>
        @include('partials/field_error', ['field' => 'members_raw'])
    </div>
</div>

@if (!empty($list->id))
    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Sender Address') }}</label>
        <div class="col-sm-8">
            <p class="form-control-static text-help">{{ $list->virtual_email }}</p>
            <p class="help-block">{{ __('Internal placeholder address used to group all broadcasts sent to this list into one conversation thread per send. It never actually receives mail.') }}</p>
        </div>
    </div>
@endif

@if (!empty($is_admin) && !empty($list->id))
    <input type="hidden" name="permissions_submitted" value="1">
    <div class="form-group">
        <label class="col-sm-3 control-label">{{ __('Permissions') }}</label>
        <div class="col-sm-8">
            <p class="help-block">{{ __('Administrators can always edit and use every list. Grant other users access below.') }}</p>

            @if (!count($users))
                <p class="text-help">{{ __('No other users.') }}</p>
            @else
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('User') }}</th>
                            <th class="text-center">{{ __('Can Edit') }}</th>
                            <th class="text-center">{{ __('Can Use') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $permission_user)
                            @php($grant = $permissions->get($permission_user->id))
                            <tr>
                                <td>{{ $permission_user->getFullName() }}</td>
                                <td class="text-center">
                                    <input type="checkbox" name="permissions[{{ $permission_user->id }}][edit]" value="1" @if ($grant && $grant->can_edit) checked @endif>
                                </td>
                                <td class="text-center">
                                    <input type="checkbox" name="permissions[{{ $permission_user->id }}][use]" value="1" @if ($grant && $grant->can_use) checked @endif>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

<div class="form-group">
    <div class="col-sm-8 col-sm-offset-3">
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        <a href="{{ route('broadcast.lists') }}" class="btn btn-default">{{ __('Cancel') }}</a>
        @if (!empty($list->id) && Auth::user()->isAdmin())
            <button type="button" class="btn btn-link text-danger pull-right" id="broadcast-list-delete" data-url="{{ route('broadcast.lists.destroy', ['id' => $list->id]) }}">{{ __('Delete List') }}</button>
        @endif
    </div>
</div>
