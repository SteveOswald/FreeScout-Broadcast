(function() {
    function init() {
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
        // Remember whether "To" was originally mandatory, so we can restore
        // that exact behavior once the list is deselected again.
        var toWasRequired = $to.prop('required') || $to.attr('required') !== undefined;

        function setToRequired(required) {
            if (!toWasRequired) {
                return;
            }

            $to.prop('required', required);
            if (required) {
                $to.attr('required', 'required');
            } else {
                $to.removeAttr('required');
            }

            // FreeScout validates this form with Parsley, which reads the
            // "required" constraint once at init time and caches it -
            // toggling the plain HTML attribute afterwards has no effect on
            // an already-initialized field. Parsley's own API has to be
            // used to actually add/remove the constraint, and reset()
            // clears the "This is a required field." error that may
            // already be showing.
            if (typeof $to.parsley === 'function') {
                var field = $to.parsley();
                if (field) {
                    if (required) {
                        field.addConstraint('required', true, 32, true);
                    } else {
                        field.removeConstraint('required');
                    }
                    field.reset();
                }
            }
        }

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
                setToRequired(true);
                $multipleWrap.removeClass('broadcast-hidden');
                return;
            }

            var email = selected.data('email');
            var label = selected.data('label');

            $hidden.val(listId);
            $to.val(null);
            injectedOption = new Option(label, email, true, true);
            $to.append(injectedOption).trigger('change');

            // A list stands in for the "To" field, so it must not still be
            // treated as a separately mandatory field (otherwise the
            // browser blocks sending even though a valid recipient list is
            // selected).
            setToRequired(false);

            // CC/BCC would defeat the point of the list (everyone would
            // see each other), so clear them when a list is chosen.
            $cc.val(null).trigger('change');
            $bcc.val(null).trigger('change');

            $('#multiple_conversations').prop('checked', false);
            $multipleWrap.addClass('broadcast-hidden');
        });
    }

    // This file is loaded as a plain <script src> at whatever point the
    // compose form is rendered, which may be before jQuery (bundled
    // elsewhere in the page) has actually loaded and run. Wait for the DOM
    // and jQuery to both be ready instead of assuming load order.
    function whenjQueryReady(callback) {
        if (window.jQuery) {
            callback();
            return;
        }
        var attempts = 0;
        var timer = setInterval(function() {
            attempts++;
            if (window.jQuery) {
                clearInterval(timer);
                callback();
            } else if (attempts > 200) {
                clearInterval(timer);
            }
        }, 100);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            whenjQueryReady(init);
        });
    } else {
        whenjQueryReady(init);
    }
})();
