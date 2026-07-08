@section('javascript')
    @parent
    /*
     * =========================
     * TIME TRACKING OTOMATIS
     * =========================
     * Tidak ada start, pause, reset, dan review modal.
     * Waktu dihitung otomatis dari ConversationsController.
     */
    $(document).on('click', '.tt-timelogs-toggle', function (e) {
        e.preventDefault();
        $('.tt-timelogs-panel').toggleClass('show');
    });
@endsection
