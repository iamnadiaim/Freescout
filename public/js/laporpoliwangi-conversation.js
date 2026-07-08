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

    /*
     * =========================
     * SATISFACTION RATINGS
     * =========================
     */
    var satisfactionRatingsEnabled = {{ $ratingsEnabled ? 'true' : 'false' }};
    var satisfactionRatingsAddMode = @json($ratingsAddMode);
    var satisfactionRatingsPlacement = @json($ratingsPlacement);
    var satisfactionRatingsShortcode = @json($ratingsShortcode);
    var satisfactionRatingsHtml = @json($ratingsEmailHtml);

    function removeExistingSatisfactionRating(content) {
        content = content || '';

        return content.replace(
            /<!-- SATISFACTION_RATING_START -->[\s\S]*?<!-- SATISFACTION_RATING_END -->/g,
            ''
        );
    }
