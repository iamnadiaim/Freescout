@section('javascript')
    @parent
/*
     * =========================
     * CUSTOM FIELD AUTO SAVE
     * =========================
     */
    var customFieldSaveTimers = {};

    function saveCustomFieldValue(input) {
        var item = input.closest('.custom-field-view-item');

        var conversationId = item.data('conversation-id');
        var customFieldId = item.data('custom-field-id');

        var status = item.find('.custom-field-save-status');
        var value = input.val();

        var compareValue = value;

        if ($.isArray(value)) {
            compareValue = value.join('|');
        }

        if (compareValue == input.attr('data-old-value')) {
            return;
        }

        status.text('Saving...');

        $.ajax({
            url: '{{ route('conversations.ajax') }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                action: 'update_custom_field_value',
                conversation_id: conversationId,
                custom_field_id: customFieldId,
                value: value
            },
            success: function (response) {
                if (response.status == 'success') {
                    input.attr('data-old-value', compareValue);

                    if (response.value_id) {
                        item.attr('data-value-id', response.value_id);
                        item.data('value-id', response.value_id);
                    }

                    status.html('<span class="custom-field-saved"><i class="glyphicon glyphicon-ok"></i> Saved</span>');

                    setTimeout(function () {
                        status.text('');
                    }, 1200);
                } else {
                    status.html('<span class="custom-field-error">' + (response.msg || 'Failed to save') + '</span>');
                }
            },
            error: function () {
                status.html('<span class="custom-field-error">Failed to save</span>');
            }
        });
    }

    $(document).on('change', '.custom-field-auto-save', function () {
        saveCustomFieldValue($(this));
    });

    $(document).on('keyup', '.custom-field-auto-save', function () {
        var input = $(this);
        var item = input.closest('.custom-field-view-item');

        var timerKey = item.data('conversation-id') + '_' + item.data('custom-field-id');

        clearTimeout(customFieldSaveTimers[timerKey]);

        customFieldSaveTimers[timerKey] = setTimeout(function () {
            saveCustomFieldValue(input);
        }, 700);
    });

    $(document).on('blur', '.custom-field-auto-save', function () {
        saveCustomFieldValue($(this));
    });

    function initCustomFieldMultiselect() {
        if (typeof $.fn.select2 === 'undefined') {
            console.log('Select2 is not loaded.');
            return;
        }

        $('select.custom-field-multiselect[multiple]').each(function () {
            var select = $(this);

            if (select.hasClass('select2-hidden-accessible')) {
                return;
            }

            select.select2({
                width: '100%',
                placeholder: 'Select one or more options',
                allowClear: true,
                closeOnSelect: false
            });
        });
    }

    $(document).ready(function () {
        initCustomFieldMultiselect();

        setTimeout(function () {
            initCustomFieldMultiselect();
        }, 500);

        setTimeout(function () {
            initCustomFieldMultiselect();
        }, 1200);
    });

    $(document).on('shown.bs.collapse shown.bs.modal ajaxComplete', function () {
        initCustomFieldMultiselect();
    });
@endsection
