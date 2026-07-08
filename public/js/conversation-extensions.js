(function () {
    function getReplyBodyContent() {
        var body = $('#body');

        if ($('.note-editable').length) {
            return $('.note-editable').first().html();
        }

        return body.val();
    }

    function setReplyBodyContent(content) {
        var body = $('#body');

        if ($('.note-editable').length) {
            $('.note-editable').first().html(content);
            body.val(content);
        } else {
            body.val(content);
        }

        body.trigger('change');
        body.focus();
    }

    function appendReplyBodyContent(replyText) {
        var currentText = getReplyBodyContent();

        replyText = $.trim(replyText || '');
        currentText = $.trim(currentText || '');

        if (replyText === '') {
            return;
        }

        if (currentText !== '') {
            setReplyBodyContent(replyText + '<br><br>' + currentText);
        } else {
            setReplyBodyContent(replyText);
        }
    }

    function removeExistingSatisfactionRating(content) {
        content = content || '';

        return content.replace(
            /<!-- SATISFACTION_RATING_START -->[\s\S]*?<!-- SATISFACTION_RATING_END -->/g,
            ''
        );
    }

    function applySatisfactionRatingsToReply(content) {
        var config = window.LaporPoliwangiSatisfaction || {};

        content = content || '';

        var cleanContent = removeExistingSatisfactionRating(content);

        if (!config.enabled) {
            return cleanContent.replace(config.shortcode || '', '');
        }

        if (config.addMode === 'shortcode') {
            if (cleanContent.indexOf(config.shortcode) === -1) {
                return cleanContent;
            }

            return cleanContent.replace(config.shortcode, config.html);
        }

        return cleanContent + '<br><br>' + config.html;
    }

    $(document).on('submit', '.form-reply', function () {
        var form = $(this);
        var isNote = form.find('input[name="is_note"]').val();

        if (isNote == '1') {
            return true;
        }

        var currentContent = getReplyBodyContent();
        var finalContent = applySatisfactionRatingsToReply(currentContent);

        setReplyBodyContent(finalContent);

        return true;
    });

    setTimeout(function () {
        var wrapper = $('#saved-replies-wrapper');
        var toolbar = $('.note-toolbar').first();

        if (toolbar.length && wrapper.length) {
            toolbar.append(wrapper);
        }
    }, 700);

    $(document).on('click', '#saved-replies-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();

        $('#saved-replies-dropdown').toggleClass('show');
        $(this).toggleClass('active');
    });

    $(document).on('click', '.saved-replies-category-toggle', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var block = $(this).closest('.saved-replies-category-block');

        $('.saved-replies-category-block').not(block).removeClass('open');
        block.toggleClass('open');
    });

    $(document).on('click', '.saved-replies-item', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var replyId = $(this).data('id');
        var replyText = $(this).attr('data-reply') || '';

        $('input[name="saved_reply_id"]').val(replyId);

        appendReplyBodyContent(replyText);

        $('#saved-replies-dropdown').removeClass('show');
        $('#saved-replies-toggle').removeClass('active');
    });

    $(document).on('click', '#saved-replies-save-this', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var currentReply = getReplyBodyContent();

        $('#save-this-reply-body').val(currentReply);
        $('#save-this-reply-name').val('');

        $('#saved-replies-dropdown').removeClass('show');
        $('#saved-replies-toggle').removeClass('active');

        $('#saveThisReplyModal').modal('show');

        setTimeout(function () {
            $('#save-this-reply-name').focus();
        }, 300);
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#saved-replies-wrapper').length) {
            $('#saved-replies-dropdown').removeClass('show');
            $('#saved-replies-toggle').removeClass('active');
        }
    });

    var customFieldSaveTimers = {};

    function saveCustomFieldValue(input) {
        var item = input.closest('.custom-field-view-item');
        var status = item.find('.custom-field-save-status');

        var value = input.val();

        if (input.is('select[multiple]')) {
            value = input.val() || [];
        }

        $.ajax({
            url: '/lapor-poliwangi/conversations/custom-field-value/update',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                conversation_id: item.data('conversation-id'),
                custom_field_id: item.data('custom-field-id'),
                value: value
            },
            success: function () {
                status.html('<span class="custom-field-saved"><i class="glyphicon glyphicon-ok"></i>Saved</span>');
            },
            error: function () {
                status.html('<span class="custom-field-error">Failed to save</span>');
            }
        });
    }

    $(document).on('change keyup', '.custom-field-auto-save', function () {
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

        setTimeout(initCustomFieldMultiselect, 500);
        setTimeout(initCustomFieldMultiselect, 1200);
    });

    $(document).on('shown.bs.collapse shown.bs.modal ajaxComplete', function () {
        initCustomFieldMultiselect();
    });

    $(document).on('click', '.tt-timelogs-toggle', function (e) {
        e.preventDefault();
        $('.tt-timelogs-panel').toggleClass('show');
    });
})();
