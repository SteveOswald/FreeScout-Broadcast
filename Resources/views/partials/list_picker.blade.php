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

<script>
(function() {
    var picker = document.getElementById('broadcast_list_id_select');
    if (!picker) {
        return;
    }

    var $picker = $(picker);
    var $to = $('#to');
    var $cc = $('#cc');
    var $bcc = $('#bcc');
    var $hidden = $('#broadcast_list_id');
    var $multipleWrap = $('#multiple-conversations-wrap');
    var injectedOption = null;

    $picker.on('change', function() {
        var selected = $picker.find('option:selected');
        var listId = $picker.val();

        if (injectedOption) {
            injectedOption.remove();
            injectedOption = null;
        }

        if (!listId) {
            $hidden.val('');
            $to.val(null).trigger('change');
            $multipleWrap.removeClass('broadcast-hidden');
            return;
        }

        var email = selected.data('email');
        var label = selected.data('label');

        $hidden.val(listId);
        $to.val(null);
        injectedOption = new Option(label, email, true, true);
        $to.append(injectedOption).trigger('change');

        // CC/BCC would defeat the point of the list (everyone would see
        // each other), so clear them when a list is chosen.
        $cc.val(null).trigger('change');
        $bcc.val(null).trigger('change');

        $('#multiple_conversations').prop('checked', false);
        $multipleWrap.addClass('broadcast-hidden');
    });
})();
</script>
