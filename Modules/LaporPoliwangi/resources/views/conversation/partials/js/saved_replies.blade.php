@section('javascript')
    @parent
/*
     * =========================
     * SAVED REPLIES
     * =========================
     */
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

    $(document).on('click', function () {
        $('#saved-replies-dropdown').removeClass('show');
        $('#saved-replies-toggle').removeClass('active');
    });

    $(document).on('click', '#saved-replies-dropdown', function (e) {
        e.stopPropagation();
    });
@endsection
