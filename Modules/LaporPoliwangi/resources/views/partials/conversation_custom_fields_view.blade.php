@php
    $is_in_chat_mode = $conversation->isInChatMode();
    /*
     * Saved Replies untuk conversation reply.
     *
     * Global ON  = tampil di semua mailbox saat membalas.
     * Global OFF = hanya tampil di mailbox asal.
     *
     * Catatan:
     * Jangan pakai whereHas/orWhereHas di sini karena bisa memicu error:
     * compact(): Undefined variable $operator
     * pada versi Laravel/FreeScout tertentu.
     */
    $saved_reply_categories = \App\SavedReply::with([
        'children' => function ($q) use ($mailbox) {
            $q->where(function ($query) use ($mailbox) {
                $query->where('mailbox_id', $mailbox->id)->orWhere('is_global', 1);
            })->orderBy('name', 'asc');
        },
    ])
        ->whereNull('parent_id')
        ->where(function ($q) use ($mailbox) {
            $q->where('mailbox_id', $mailbox->id)->orWhere('is_global', 1);
        })
        ->orderBy('name', 'asc')
        ->get();

    $satisfactionRatingSetting = \App\SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();

    $satisfactionRatings = \App\SatisfactionRating::where('conversation_id', $conversation->id)
        ->get()
        ->keyBy('thread_id');

    /*
     * Satisfaction Ratings email block.
     *
     * This block will be inserted into the reply body before the email is sent.
     * thread_id uses 0 because the outgoing thread has not been created yet
     * at the time the reply editor is rendered.
     */
    $ratingsEnabled = $satisfactionRatingSetting && $satisfactionRatingSetting->enabled;

    $ratingsShortcode = '{%ratings.add%}';

    $ratingsAddMode =
        $satisfactionRatingSetting && $satisfactionRatingSetting->add_ratings_mode
            ? $satisfactionRatingSetting->add_ratings_mode
            : 'all';

    $ratingsPlacement = $satisfactionRatingSetting ? $satisfactionRatingSetting->placement : 'above';

    $ratingsText =
        $satisfactionRatingSetting && $satisfactionRatingSetting->ratings_text
            ? $satisfactionRatingSetting->ratings_text
            : 'How would you rate my reply?';

    $greatText =
        $satisfactionRatingSetting && $satisfactionRatingSetting->great_text
            ? $satisfactionRatingSetting->great_text
            : 'Great';

    $okayText =
        $satisfactionRatingSetting && $satisfactionRatingSetting->okay_text
            ? $satisfactionRatingSetting->okay_text
            : 'Okay';

    $notGoodText =
        $satisfactionRatingSetting && $satisfactionRatingSetting->not_good_text
            ? $satisfactionRatingSetting->not_good_text
            : 'Not Good';

    $customerRatingEmail = '';

    if (!empty($conversation->customer_email)) {
        $customerRatingEmail = $conversation->customer_email;
    } elseif (!empty($customer) && method_exists($customer, 'getMainEmail')) {
        $customerRatingEmail = $customer->getMainEmail();
    }

    /*
     * Make sure this route exists in web.php:
     *
     * Route::get('/mailbox/{mailbox_id}/satisfaction-ratings/rate/{conversation_id}/{thread_id}/{rating}',
     *     'SatisfactionRatingController@rateFromEmail'
     * )->name('mailboxes.satisfaction_ratings.rate_from_email');
     */
    $ratingThreadId = 0;

    $ratingGreatUrl =
        route('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $mailbox->id,
            'conversation_id' => $conversation->id,
            'thread_id' => $ratingThreadId,
            'rating' => 'great',
        ]) .
        '?email=' .
        urlencode($customerRatingEmail);

    $ratingOkayUrl =
        route('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $mailbox->id,
            'conversation_id' => $conversation->id,
            'thread_id' => $ratingThreadId,
            'rating' => 'okay',
        ]) .
        '?email=' .
        urlencode($customerRatingEmail);

    $ratingNotGoodUrl =
        route('mailboxes.satisfaction_ratings.rate_from_email', [
            'mailbox_id' => $mailbox->id,
            'conversation_id' => $conversation->id,
            'thread_id' => $ratingThreadId,
            'rating' => 'not_good',
        ]) .
        '?email=' .
        urlencode($customerRatingEmail);

    $ratingsEmailHtml =
        '' .
        '<!-- SATISFACTION_RATING_START -->' .
        '<div class="satisfaction-rating-email-block" data-satisfaction-rating="1" style="margin-top:16px;margin-bottom:16px;">' .
        '<p style="margin:0 0 8px 0;">' .
        e($ratingsText) .
        '</p>' .
        '<p style="margin:0;">' .
        '<a href="' .
        e($ratingGreatUrl) .
        '">' .
        e($greatText) .
        '</a>' .
        ' &nbsp;|&nbsp; ' .
        '<a href="' .
        e($ratingOkayUrl) .
        '">' .
        e($okayText) .
        '</a>' .
        ' &nbsp;|&nbsp; ' .
        '<a href="' .
        e($ratingNotGoodUrl) .
        '">' .
        e($notGoodText) .
        '</a>' .
        '</p>' .
        '</div>' .
        '<!-- SATISFACTION_RATING_END -->';
@endphp
@php
                    /*
                     * Time tracking otomatis:
                     * - dimulai saat laporan dibuka/dibaca
                     * - disimpan saat tiket closed
                     * - tidak memakai setting/tombol manual lagi
                     */
                    $isAssignedToMe = (int) $conversation->user_id === (int) Auth::id();

                    $timeTrackingLogs = \App\TimeTrackingLog::with('user')
                        ->where('conversation_id', $conversation->id)
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    $totalTrackedSeconds = $timeTrackingLogs->sum('seconds');

                    $canViewTimelogs = Auth::user()->isAdmin() || $isAssignedToMe;
                @endphp

                @php
                    /*
                     * Time tracking otomatis:
                     * - dimulai saat laporan dibuka/dibaca
                     * - disimpan saat tiket closed
                     * - tidak memakai setting/tombol manual lagi
                     */
                    $isAssignedToMe = (int) $conversation->user_id === (int) Auth::id();

                    $timeTrackingLogs = \App\TimeTrackingLog::with('user')
                        ->where('conversation_id', $conversation->id)
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    $totalTrackedSeconds = $timeTrackingLogs->sum('seconds');

                    $canViewTimelogs = Auth::user()->isAdmin() || $isAssignedToMe;
                @endphp

                @php
                    /*
                     * Time tracking otomatis:
                     * - dimulai saat laporan dibuka/dibaca
                     * - disimpan saat tiket closed
                     * - tidak memakai setting/tombol manual lagi
                     */
                    $isAssignedToMe = (int) $conversation->user_id === (int) Auth::id();

                    $timeTrackingLogs = \App\TimeTrackingLog::with('user')
                        ->where('conversation_id', $conversation->id)
                        ->orderBy('updated_at', 'desc')
                        ->get();

                    $totalTrackedSeconds = $timeTrackingLogs->sum('seconds');

                    $canViewTimelogs = Auth::user()->isAdmin() || $isAssignedToMe;
                @endphp

                @if ($isAssignedToMe || $canViewTimelogs)
                    <div class="conv-block tt-conv-timer" data-conversation-id="{{ $conversation->id }}">

                        <div class="tt-timer-main-row">
                            <span class="tt-auto-badge">
                                <i class="glyphicon glyphicon-time"></i>
                                Time Tracking Otomatis
                            </span>

                            <span class="tt-total-time">
                                Total time:
                                <span class="tt-total-value">{{ gmdate('H:i:s', (int) $totalTrackedSeconds) }}</span>
                            </span>

                            @if ($canViewTimelogs)
                                <button type="button" class="tt-timelogs-toggle">
                                    Timelogs <span class="caret"></span>
                                </button>
                            @endif
                        </div>

                        @if ($canViewTimelogs)
                            <div class="tt-timelogs-panel">
                                <table class="tt-timelogs-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 90px;">Status</th>
                                            <th>User</th>
                                            <th class="tt-log-time">Time</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($timeTrackingLogs as $log)
                                        @php
                                            $logUserName = 'Unknown User';

                                            if ($log->user) {
                                                $logUserName = trim(
                                                    ($log->user->first_name ?? '') .
                                                        ' ' .
                                                        ($log->user->last_name ?? ''),
                                                );

                                                if (!$logUserName) {
                                                    $logUserName = $log->user->email ?? 'Unknown User';
                                                }
                                            }

                                            $icon = 'glyphicon-ok';
                                            $iconColor = '#4caf50';

                                            $note = (string) $log->note;

                                            if (strpos($note, 'to Closed') !== false) {
                                                $icon = 'glyphicon-ok';
                                                $iconColor = '#4caf50';
                                            } elseif (strpos($note, 'to Pending') !== false) {
                                                $icon = 'glyphicon-time';
                                                $iconColor = '#ff9800';
                                            } elseif (strpos($note, 'to Active') !== false) {
                                                $icon = 'glyphicon-inbox';
                                                $iconColor = '#0078d7';
                                            } elseif (strpos($note, 'to Spam') !== false) {
                                                $icon = 'glyphicon-ban-circle';
                                                $iconColor = '#f44336';
                                            } else {
                                                if ($conversation->status == \App\Conversation::STATUS_CLOSED) {
                                                    $icon = 'glyphicon-ok';
                                                    $iconColor = '#4caf50';
                                                } elseif ($conversation->status == \App\Conversation::STATUS_PENDING) {
                                                    $icon = 'glyphicon-time';
                                                    $iconColor = '#ff9800';
                                                } elseif ($conversation->status == \App\Conversation::STATUS_ACTIVE) {
                                                    $icon = 'glyphicon-inbox';
                                                    $iconColor = '#0078d7';
                                                } elseif ($conversation->status == \App\Conversation::STATUS_SPAM) {
                                                    $icon = 'glyphicon-ban-circle';
                                                    $iconColor = '#f44336';
                                                }
                                            }
                                        @endphp

                                        <tr>
                                            <td>
                                                <span class="tt-log-status" style="color: {{ $iconColor }};">
                                                    <i class="glyphicon {{ $icon }}"></i>
                                                </span>
                                            </td>
                                            <td>{{ $logUserName }}</td>
                                                <td class="tt-log-time">{{ gmdate('H:i:s', (int) $log->seconds) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-muted">
                                                    Belum ada waktu yang tersimpan. Waktu akan tersimpan saat tiket
                                                    di-Closed.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
                @if (isset($custom_fields) && $custom_fields->count())
                    <div class="conv-block custom-field-view-block">
                        <div class="row">
                            @foreach ($custom_fields as $field)
                                @php
                                    // Ambil value berdasarkan custom_field_id.
                                    // Kalau belum ada value, tampilkan kosong.
                                    $value = isset($custom_field_values[$field->id])
                                        ? $custom_field_values[$field->id]
                                        : '';

                                    $decodedValue = json_decode($value, true);

                                    $options = [];

                                    if (is_array($field->options)) {
                                        $options = $field->options;
                                    } elseif (!empty($field->options)) {
                                        $decodedOptions = json_decode($field->options, true);
                                        $options = is_array($decodedOptions) ? $decodedOptions : [];
                                    }
                                @endphp

                                <div class="custom-field-view-item" data-conversation-id="{{ $conversation->id }}"
                                    data-custom-field-id="{{ $field->id }}">

                                    <label class="custom-field-view-label">
                                        {{ $field->nama_field ?? '-' }}

                                        @if ($field->required)
                                            <span class="text-danger">*</span>
                                        @endif
                                    </label>

                                    @if ($field->type_field == 'textarea')
                                        <textarea class="form-control custom-field-auto-save custom-field-edit-textarea"
                                            data-old-value="{{ $value }}">{{ $value }}</textarea>
                                    @elseif ($field->type_field == 'number')
                                        <input type="number"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @elseif ($field->type_field == 'date')
                                        <input type="date"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @elseif ($field->type_field == 'dropdown')
                                        <select class="form-control custom-field-auto-save custom-field-edit-input"
                                            data-old-value="{{ $value }}">

                                            <option value="">- Pilih -</option>

                                            @foreach ($options as $option)
                                                <option value="{{ $option }}"
                                                    @if ($value == $option) selected @endif>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif ($field->type_field == 'multiselect')
                                        <select multiple="multiple" id="custom-field-multiselect-{{ $field->id }}"
                                            class="custom-field-auto-save custom-field-multiselect"
                                            data-placeholder="Select one or more options"
                                            data-old-value="{{ is_array($decodedValue) ? implode('|', $decodedValue) : '' }}"
                                            style="width: 100%;">

                                            @foreach ($options as $option)
                                                <option value="{{ $option }}"
                                                    @if (is_array($decodedValue) && in_array($option, $decodedValue)) selected @endif>
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else
                                        <input type="text"
                                            class="form-control custom-field-auto-save custom-field-edit-input"
                                            value="{{ $value }}" data-old-value="{{ $value }}">
                                    @endif

                                    <small class="custom-field-save-status text-help"></small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
{{-- Saved Replies Toolbar Dropdown --}}
                                <div class="saved-replies-wrapper" id="saved-replies-wrapper">
                                    <button type="button" class="saved-replies-button" id="saved-replies-toggle"
                                        title="Saved Replies" data-toggle="tooltip">
                                        <i class="glyphicon glyphicon-comment"></i>
                                    </button>

                                    <div class="saved-replies-dropdown" id="saved-replies-dropdown">
                                        @if ($saved_reply_categories->count())
                                            @foreach ($saved_reply_categories as $category)
                                                <div class="saved-replies-category-block">
                                                    <button type="button" class="saved-replies-category-toggle">
                                                        {{ $category->name }}
                                                        <span class="caret"></span>
                                                    </button>

                                                    <div class="saved-replies-category-items">

                                                        {{-- Standalone saved reply --}}
                                                        @if (!$category->children->count() && !empty($category->reply))
                                                            <button type="button" class="saved-replies-item"
                                                                data-id="{{ $category->id }}"
                                                                data-reply="{{ e($category->reply) }}">
                                                                Use this reply
                                                            </button>

                                                            {{-- Category with child replies --}}
                                                        @elseif ($category->children && $category->children->count())
                                                            @foreach ($category->children as $reply)
                                                                <button type="button" class="saved-replies-item"
                                                                    data-id="{{ $reply->id }}"
                                                                    data-reply="{{ e($reply->reply) }}">
                                                                    {{ $reply->name }}
                                                                </button>
                                                            @endforeach

                                                            {{-- Empty category --}}
                                                        @else
                                                            <div class="saved-replies-empty-small">
                                                                No replies yet.
                                                            </div>
                                                        @endif

                                                    </div>
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="saved-replies-empty">
                                                No saved replies yet.
                                            </div>
                                        @endif

                                        <div class="saved-replies-save-new">
                                            <button type="button" class="saved-replies-save-this"
                                                id="saved-replies-save-this">
                                                Save This Reply...
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal fade" id="saveThisReplyModal" tabindex="-1" role="dialog"
        aria-labelledby="saveThisReplyModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">

                <form method="POST" action="{{ route('mailboxes.saved_replies.store', ['id' => $mailbox->id]) }}">
                    {{ csrf_field() }}

                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>

                        <h4 class="modal-title" id="saveThisReplyModalLabel">
                            Save This Reply
                        </h4>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" name="name" id="save-this-reply-name" class="form-control"
                                required>
                        </div>

                        @if ($saved_reply_categories->count())
                            <div class="form-group">
                                <label>Category</label>

                                <select name="parent_id" id="save-this-reply-parent" class="form-control" required>
                                    <option value="">-- Select Category --</option>

                                    @foreach ($saved_reply_categories as $category)
                                        @if ($category->mailbox_id == $mailbox->id)
                                            <option value="{{ $category->id }}">
                                                {{ $category->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="form-group">
                            <label>Reply</label>
                            <textarea name="reply" id="save-this-reply-body" class="form-control" rows="8" required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_global" value="1">
                                Global
                            </label>

                            <p class="text-muted" style="margin-top: 6px;">
                                If Global is enabled, this saved reply can be used in all mailboxes when replying.
                                If it is disabled, it will only appear in this mailbox.
                            </p>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-link" data-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit" class="btn btn-primary">
                            Save Reply
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
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

    function insertRatingAboveSignature(content, ratingHtml) {
        var cleanContent = removeExistingSatisfactionRating(content);

        var signatureMarkers = [
            '<br>--',
            '<br />--',
            '<p>--',
            '--'
        ];

        var foundIndex = -1;

        for (var i = 0; i < signatureMarkers.length; i++) {
            var markerIndex = cleanContent.indexOf(signatureMarkers[i]);

            if (markerIndex !== -1 && (foundIndex === -1 || markerIndex < foundIndex)) {
                foundIndex = markerIndex;
            }
        }

        if (foundIndex !== -1) {
            return cleanContent.substring(0, foundIndex)
                + '<br><br>'
                + ratingHtml
                + '<br><br>'
                + cleanContent.substring(foundIndex);
        }

        return cleanContent + '<br><br>' + ratingHtml;
    }

    function insertRatingBelowSignature(content, ratingHtml) {
        var cleanContent = removeExistingSatisfactionRating(content);

        return cleanContent + '<br><br>' + ratingHtml;
    }

    function applySatisfactionRatingsToReply(content) {
        content = content || '';

        var cleanContent = removeExistingSatisfactionRating(content);

        if (!satisfactionRatingsEnabled) {
            return cleanContent.replace(satisfactionRatingsShortcode, '');
        }

        if (satisfactionRatingsAddMode === 'shortcode') {
            if (cleanContent.indexOf(satisfactionRatingsShortcode) === -1) {
                return cleanContent;
            }

            return cleanContent.replace(satisfactionRatingsShortcode, satisfactionRatingsHtml);
        }

        if (satisfactionRatingsPlacement === 'below') {
            return insertRatingBelowSignature(cleanContent, satisfactionRatingsHtml);
        }

        return insertRatingAboveSignature(cleanContent, satisfactionRatingsHtml);
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

    $(document).ready(function() {
        var isClosed = {{ isset($conversation) && $conversation->status == \App\Conversation::STATUS_CLOSED ? 'true' : 'false' }};
        var totalSeconds = {{ isset($totalTrackedSeconds) ? (int) $totalTrackedSeconds : 0 }};
        @if(isset($conversation))
        var totalValueEl = $('.tt-conv-timer[data-conversation-id="{{ $conversation->id }}"] .tt-total-value');

        if (!isClosed && totalValueEl.length > 0) {
            setInterval(function() {
                totalSeconds++;
                var h = Math.floor(totalSeconds / 3600);
                var m = Math.floor((totalSeconds % 3600) / 60);
                var s = totalSeconds % 60;

                var display = 
                    (h < 10 ? "0" + h : h) + ":" + 
                    (m < 10 ? "0" + m : m) + ":" + 
                    (s < 10 ? "0" + s : s);

                totalValueEl.text(display);
            }, 1000);
        }
        @endif
    });
