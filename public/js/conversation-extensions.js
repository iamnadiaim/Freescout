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

    var attempts = 0;
    var initSavedReplies = setInterval(function () {
        attempts++;
        var wrappers = $('.js-saved-replies-wrapper');
        
        var anyMoved = false;
        wrappers.each(function() {
            var wrapper = $(this);
            // Ignore if already moved inside a toolbar
            if (wrapper.parent().hasClass('note-toolbar') || wrapper.parent().hasClass('redactor-toolbar')) {
                return;
            }
            
            // Find the closest editor toolbar by traversing up the DOM
            var container = wrapper.parent();
            var toolbar = container.find('.note-toolbar, .redactor-toolbar').first();
            
            while (!toolbar.length && !container.is('body')) {
                container = container.parent();
                toolbar = container.find('.note-toolbar, .redactor-toolbar').first();
            }
            
            if (toolbar.length) {
                toolbar.append(wrapper);
                anyMoved = true;
                
                // Bind click events DIRECTLY to the elements, bypassing document delegation
                // which gets blocked by Summernote's stopPropagation on the toolbar.
                wrapper.find('.js-saved-replies-toggle').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    wrapper.find('.js-saved-replies-dropdown').toggleClass('show');
                    $(this).toggleClass('active');
                });
                
                wrapper.find('.saved-replies-category-toggle').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var block = $(this).closest('.saved-replies-category-block');
                    $('.saved-replies-category-block').not(block).removeClass('open');
                    block.toggleClass('open');
                });
                
                wrapper.find('.saved-replies-item').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var replyId = $(this).data('id');
                    var replyText = $(this).attr('data-reply') || '';
                    $('input[name="saved_reply_id"]').val(replyId);
                    appendReplyBodyContent(replyText);
                    wrapper.find('.js-saved-replies-dropdown').removeClass('show');
                    wrapper.find('.js-saved-replies-toggle').removeClass('active');
                });
                
                wrapper.find('.js-saved-replies-save-this').off('click').on('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var currentReply = getReplyBodyContent();
                    $('#save-this-reply-body').val(currentReply);
                    $('#save-this-reply-name').val('');
                    wrapper.find('.js-saved-replies-dropdown').removeClass('show');
                    wrapper.find('.js-saved-replies-toggle').removeClass('active');
                    $('#saveThisReplyModal').modal('show');
                    setTimeout(function () {
                        $('#save-this-reply-name').focus();
                    }, 300);
                });
            }
        });

        if (anyMoved || attempts > 20) {
            clearInterval(initSavedReplies); // Give up after 10 seconds or when moved
        }
    }, 500);

    // Click outside to close is still on document, this is fine because clicks OUTSIDE 
    // the toolbar will reach the document and close the dropdown.
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.js-saved-replies-wrapper').length) {
            $('.js-saved-replies-dropdown').removeClass('show');
            $('.js-saved-replies-toggle').removeClass('active');
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

    $(document).on('change', '.custom-field-auto-save', function () {
        var input = $(this);
        var item = input.closest('.custom-field-view-item');

        var timerKey = item.data('conversation-id') + '_' + item.data('custom-field-id');

        clearTimeout(customFieldSaveTimers[timerKey]);

        customFieldSaveTimers[timerKey] = setTimeout(function () {
            saveCustomFieldValue(input);
        }, 100);
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
        $(this).closest('.tt-conv-timer').find('.tt-timelogs-panel').toggleClass('show');
    });
})();
