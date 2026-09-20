<div class="form-group email-conv-fields" id="broadcast-list-picker-group">
    <label for="broadcast_list_id_select" class="col-sm-2 control-label">{{ __('Recipient List') }}</label>

    <div class="col-sm-9">
        @if (count($lists))
            <select class="form-control" id="broadcast_list_id_select">
                <option value="">{{ __('— Send to individual recipients above —') }}</option>
                @foreach ($lists as $list)
                    <option value="{{ $list->id }}" data-email="{{ $list->virtual_email }}" data-label="{{ $list->name }} ({{ $list->members_count }})">{{ $list->name }} ({{ $list->members_count }} {{ __('recipients') }})</option>
                @endforeach
            </select>
            <input type="hidden" name="broadcast_list_id" id="broadcast_list_id" value="">
            <p class="help-block">{{ __('Sending to a list emails every recipient individually - they will not see each other\'s address - and creates a single conversation for the whole broadcast. Any reply from a recipient starts a brand new conversation.') }}</p>
        @else
            <p class="form-control-static text-help">
                {{ __('No recipient lists yet.') }}
                <a href="{{ route('broadcast.lists.create') }}" target="_blank">{{ __('Create one') }}</a>
            </p>
        @endif
    </div>
</div>

<style>
#multiple-conversations-wrap.broadcast-hidden { display: none !important; }
</style>

{{-- Loaded as an external file (not inline) because FreeScout's Content-Security-Policy
     (script-src 'self' 'nonce-...') blocks inline <script> tags that don't carry a
     matching per-request nonce; 'self' allows same-origin script files unconditionally. --}}
<script src="{{ route('broadcast.list_picker_js') }}"></script>
