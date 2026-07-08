@section('javascript')
    @parent
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
@endsection
