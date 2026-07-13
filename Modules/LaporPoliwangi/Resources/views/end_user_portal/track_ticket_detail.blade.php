<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $conversation->subject }} - {{ $mailbox->name }}</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f4f4f4;
            color: #2f3d4a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .ticket-detail-page {
            padding-top: 20px;
            flex: 1;
            background: #ffffff;
            border-top: 1px solid #d9e0e7;
        }

        .detail-header {
            padding: 0 18px 18px;
            border-bottom: 1px solid #e3e8ef;
            background: #ffffff;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 15px;
            color: #3a7dbd;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .detail-title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .detail-title {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
            color: #2f3d4a;
            line-height: 1.4;
        }

        .status-badge {
            display: inline-block;
            padding: 7px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-closed {
            background: #8c97a3;
            color: #ffffff;
        }

        .status-open {
            background: #0a84df;
            color: #ffffff;
        }

        .thread-list {
            margin: 0 auto;
            background: #ffffff;
            border-left: 1px solid #e3e8ef;
            border-right: 1px solid #e3e8ef;
        }

        .thread-item {
            display: flex;
            gap: 16px;
            padding: 18px 12px;
            border-bottom: 1px solid #e3e8ef;
        }

        .thread-item.customer {
            background: #ffffff;
        }

        .thread-item.user {
            background: #f3f7fb;
        }

        .avatar {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            background: #f0f3f4;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .16);
            flex: 0 0 46px;

            display: flex;
            align-items: center;
            justify-content: center;
            color: #dce1e4;
        }

        .avatar svg {
            width: 38px;
            height: 38px;
            display: block;
        }

        .thread-content {
            flex: 1;
            min-width: 0;
        }

        .thread-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 22px;
        }

        .thread-name {
            font-size: 16px;
            font-weight: bold;
            color: #25384b;
        }

        .thread-date {
            color: #a6b1bd;
            font-size: 14px;
            white-space: nowrap;
        }

        .thread-body {
            font-size: 16px;
            line-height: 1.6;
            color: #34495e;
        }

        .reply-box {
            padding: 24px 18px 0;
            background: #ffffff;
        }

        .reply-textarea {
            width: 100%;
            min-height: 145px;
            border: 1px solid #cfd8e3;
            border-radius: 3px;
            padding: 12px;
            font-size: 15px;
            resize: vertical;
            outline: none;
        }

        .reply-textarea:focus {
            border-color: #0078d4;
            box-shadow: 0 0 0 2px rgba(0, 120, 212, .12);
        }

        .attachment-box {
            margin-top: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 13px 20px;
            border: 1px dashed #cfd8e3;
            color: #9aa6b2;
            font-size: 15px;
            cursor: pointer;
        }

        .reply-btn {
            width: 100%;
            margin-top: 18px;
            border: none;
            background: #0875cf;
            color: #ffffff;
            font-size: 16px;
            padding: 13px;
            border-radius: 5px;
            cursor: pointer;
        }

        .reply-btn:hover {
            background: #006bbd;
        }

        .satisfaction-rating-box {
            margin-top: 18px;
            padding: 15px;
            border: 1px solid #d8e2ef;
            border-radius: 6px;
            background: #ffffff;
        }

        .satisfaction-rating-title {
            font-size: 14px;
            font-weight: bold;
            color: #25384b;
            margin-bottom: 12px;
        }

        .satisfaction-rating-current {
            margin-bottom: 10px;
            font-size: 14px;
            color: #34495e;
        }

        .satisfaction-rating-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .satisfaction-rating-option {
            border: 1px solid #cfd8e3;
            border-radius: 5px;
            padding: 8px 12px;
            background: #f9fbfd;
            cursor: pointer;
            font-size: 14px;
        }

        .satisfaction-rating-option input {
            margin-right: 5px;
        }

        .satisfaction-rating-comment-label {
            display: block;
            margin-bottom: 6px;
            font-size: 14px;
            color: #34495e;
        }

        .satisfaction-rating-comment {
            width: 100%;
            min-height: 80px;
            border: 1px solid #cfd8e3;
            border-radius: 4px;
            padding: 10px;
            resize: vertical;
            margin-bottom: 10px;
        }

        .satisfaction-rating-submit {
            border: none;
            background: #0875cf;
            color: #ffffff;
            padding: 9px 16px;
            border-radius: 4px;
            cursor: pointer;
        }

        .satisfaction-rating-submit:hover {
            background: #006bbd;
        }

        /* ALERT SUCCESS / ERROR */
        .portal-alert {
            position: fixed;
            top: 75px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;

            width: fit-content;
            max-width: 340px;
            padding: 9px 12px;
            border-radius: 5px;
            font-size: 13px;
            line-height: 1.4;

            display: flex;
            align-items: flex-start;
            gap: 8px;

            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
        }

        .portal-alert-success {
            background: #e8f7ee;
            border: 1px solid #b9e4c9;
            color: #1f7a3f;
        }

        .portal-alert-danger {
            background: #fdecec;
            border: 1px solid #f4b9b9;
            color: #b42318;
        }

        .portal-alert-icon {
            font-size: 16px;
            line-height: 1.4;
            flex: 0 0 auto;
        }

        .portal-alert-content {
            flex: 1;
        }

        @media (max-width: 700px) {
            .portal-alert {
                margin: 0 12px 15px;
                padding: 12px 14px;
                font-size: 13px;
            }
        }

        @media (max-width: 700px) {
            .detail-title-row {
                align-items: flex-start;
            }

            .detail-title {
                font-size: 20px;
            }

            .thread-item {
                padding: 22px 15px;
                gap: 12px;
            }

            .avatar {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
            }

            .thread-name {
                font-size: 17px;
            }

            .thread-body {
                font-size: 15px;
            }

            .thread-head {
                display: block;
            }

            .thread-date {
                display: block;
                margin-top: 5px;
            }
        }
    </style>
</head>

<body>

    @include('laporpoliwangi::end_user_portal.partials.navbar')

    @php
        $isClosed = $conversation->status == \App\Conversation::STATUS_CLOSED;
    @endphp

    <div class="ticket-detail-page">
        @if (session('success'))
            <div class="portal-alert portal-alert-success">
                <span class="portal-alert-icon">✓</span>
                <div class="portal-alert-content">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="portal-alert portal-alert-danger">
                <span class="portal-alert-icon">!</span>
                <div class="portal-alert-content">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="detail-header">
            <a href="{{ route('laporpoliwangi.end_user_portal.my_ticket') }}" class="back-link">
                &laquo; Kembali
            </a>

            <div class="detail-title-row">
                <div>
                    <p>[#{{ $conversation->number }}] {{ $conversation->subject }}</p>
                </div>

                <span class="status-badge {{ $isClosed ? 'status-closed' : 'status-open' }}">
                    {{ $isClosed ? 'Closed' : 'Open' }}
                </span>
            </div>
        </div>

        <div class="thread-list">
            @foreach ($threads as $thread)
                @php
                    $isCustomer = $thread->type == \App\Thread::TYPE_CUSTOMER;
                    $threadName = $isCustomer ? ($thread->from ?: $email) : $mailbox->name . ' Support';
                    /*
                     * Original body is used to detect whether this thread should show rating.
                     */
                    $threadBodyOriginal = (string) $thread->body;

                    /*
                     * Display body is shown to the customer.
                     * Hide the shortcode so {%ratings.add%} is not visible.
                     */
                    $threadBodyDisplay = str_replace('{%ratings.add%}', '', $threadBodyOriginal);

                    /*
                     * If rating HTML block was inserted into the email body,
                     * hide it here because the portal already displays its own rating box.
                     */
                    $threadBodyDisplay = preg_replace(
                        '/<!-- SATISFACTION_RATING_START -->(.*?)<!-- SATISFACTION_RATING_END -->/is',
                        '',
                        $threadBodyDisplay,
                    );

                    /*
                     * Also remove generated rating blocks if the markers are missing but the data attribute exists.
                     */
                    $threadBodyDisplay = preg_replace(
                        '/<div[^>]*data-satisfaction-rating=["\']1["\'][\s\S]*?<\/div>/i',
                        '',
                        $threadBodyDisplay,
                    );
                @endphp

                <div class="thread-item {{ $isCustomer ? 'customer' : 'user' }}">
                    <div class="avatar">
                        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path
                                d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.31 0-10 1.66-10 5v1.5c0 .83.67 1.5 1.5 1.5h17c.83 0 1.5-.67 1.5-1.5V19c0-3.34-6.69-5-10-5z">
                            </path>
                        </svg>
                    </div>

                    <div class="thread-content">
                        <div class="thread-head">
                            <div class="thread-name">
                                {{ $threadName }}
                            </div>

                            <div class="thread-date">
                                {{ $thread->created_at ? $thread->created_at->format('M d, Y H:i') : '' }}
                            </div>
                        </div>

                        <div class="thread-body">
                            {!! $threadBodyDisplay !!}
                        </div>
                        {{-- SATISFACTION RATING (HIDDEN FOR TRACK TICKET) --}}
                    </div>
                </div>
            @endforeach
        </div>

        @if (!$isClosed)
            <div class="reply-box">
                <form method="POST"
                    action="{{ route('laporpoliwangi.end_user_portal.track_reply', $conversation->number) }}"
                    enctype="multipart/form-data">
                    {{ csrf_field() }}

                    <textarea name="message" class="reply-textarea" placeholder="Pesan*" required>{{ old('message', '') }}</textarea>

                    <label class="attachment-box" for="reply_attachments">
                        <span class="attachment-icon">
                            <svg width="17" height="17" viewBox="0 0 24 24" fill="currentColor"
                                aria-hidden="true">
                                <path
                                    d="M16.5 6.5v10.25c0 2.48-2.02 4.5-4.5 4.5s-4.5-2.02-4.5-4.5V5.5c0-1.52 1.23-2.75 2.75-2.75S13 3.98 13 5.5v10.75c0 .55-.45 1-1 1s-1-.45-1-1V6.5H9.5v9.75c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V5.5c0-2.35-1.9-4.25-4.25-4.25S6 3.15 6 5.5v11.25c0 3.31 2.69 6 6 6s6-2.69 6-6V6.5h-1.5z" />
                            </svg>
                        </span>
                        <span>Add attachments</span>

                        <input type="file" name="attachments[]" id="reply_attachments" multiple
                            style="display:none;">
                    </label>

                    <button type="submit" class="reply-btn">
                        Reply
                    </button>
                </form>
            </div>
        @endif


    </div>

    @include('laporpoliwangi::end_user_portal.partials.footer')
    <script {!! \Helper::cspNonceAttr() !!}>
        document.addEventListener('DOMContentLoaded', function() {
            var ratingForms = document.querySelectorAll('.satisfaction-rating-form');

            ratingForms.forEach(function(form) {
                var savingModeInput = form.querySelector('input[name="saving_mode"]');

                if (!savingModeInput || savingModeInput.value !== 'immediate') {
                    return;
                }

                var ratingInputs = form.querySelectorAll('input[name="rating"]');

                ratingInputs.forEach(function(input) {
                    input.addEventListener('change', function() {
                        form.submit();
                    });
                });
            });
        });
    </script>
</body>


</html>
