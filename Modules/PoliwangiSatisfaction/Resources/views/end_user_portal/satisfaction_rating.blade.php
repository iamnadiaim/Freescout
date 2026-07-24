@php
    $showSatisfactionRating = false;
    $isAgentReply = $thread->type == \App\Thread::TYPE_MESSAGE;

    if (isset($ratingSetting) && $ratingSetting && $ratingSetting->enabled && $isAgentReply) {
        $threadBodyForRating = (string) $thread->body;

        /*
         * Mode all:
         * Rating is shown for every operator reply.
         */
        if ($ratingSetting->add_ratings_mode == 'all') {
            $showSatisfactionRating = true;
        }

        /*
         * Mode shortcode:
         * Rating is only shown when this thread contains the shortcode
         * or the generated satisfaction rating block.
         */
        if ($ratingSetting->add_ratings_mode == 'shortcode') {
            $showSatisfactionRating =
                strpos($threadBodyForRating, '{%ratings.add%}') !== false ||
                strpos($threadBodyForRating, 'SATISFACTION_RATING_START') !== false ||
                strpos($threadBodyForRating, 'data-satisfaction-rating="1"') !== false;
        }
    }
@endphp

@if ($showSatisfactionRating)
    <div class="satisfaction-rating-box">
        <div class="satisfaction-rating-title">
            {{ $ratingSetting->ratings_text ?: 'How would you rate this response?' }}
        </div>

        @if ($currentRating)
            <div class="satisfaction-rating-current">
                Your rating:
                <strong>
                    {{ $currentRating->rating_emoji }}
                    {{ $currentRating->rating_label }}
                </strong>
            </div>
        @endif

        @if(Route::has('PoliwangiPortal.end_user_portal.submit_satisfaction_rating'))
        <form method="POST" class="satisfaction-rating-form"
            action="{{ route('PoliwangiPortal.end_user_portal.submit_satisfaction_rating', [$mailbox->id, $conversation->id]) }}">
            {{ csrf_field() }}

            <input type="hidden" name="thread_id" value="{{ $thread->id }}">
            <input type="hidden" name="saving_mode"
                value="{{ $ratingSetting->saving_mode ?: 'after_send' }}">

            <div class="satisfaction-rating-options">
                <label class="satisfaction-rating-option">
                    <input type="radio" name="rating" value="great"
                        {{ $currentRating && $currentRating->rating == 'great' ? 'checked' : '' }}
                        required>
                    <span>😊 {{ $ratingSetting->great_text ?: 'Great' }}</span>
                </label>

                <label class="satisfaction-rating-option">
                    <input type="radio" name="rating" value="okay"
                        {{ $currentRating && $currentRating->rating == 'okay' ? 'checked' : '' }}
                        required>
                    <span>😐 {{ $ratingSetting->okay_text ?: 'Okay' }}</span>
                </label>

                <label class="satisfaction-rating-option">
                    <input type="radio" name="rating" value="not_good"
                        {{ $currentRating && $currentRating->rating == 'not_good' ? 'checked' : '' }}
                        required>
                    <span>☹️ {{ $ratingSetting->not_good_text ?: 'Not Good' }}</span>
                </label>
            </div>

            @if (($ratingSetting->saving_mode ?: 'after_send') == 'after_send')
                <label class="satisfaction-rating-comment-label">
                    {{ $ratingSetting->comment_box_text ?: 'Additional comment' }}
                </label>

                <textarea name="comment" class="form-control satisfaction-rating-comment" rows="3" maxlength="1000"
                    placeholder="{{ $ratingSetting->comment_placeholder ?: '(optional)' }}">{{ $currentRating ? $currentRating->comment : '' }}</textarea>

                <button type="submit" class="btn btn-primary satisfaction-rating-submit">
                    {{ $ratingSetting->send_button_text ?: 'Send' }}
                </button>
            @else
                <textarea name="comment" style="display:none;">{{ $currentRating ? $currentRating->comment : '' }}</textarea>

                <div class="satisfaction-rating-current">
                    Your rating will be saved immediately after selecting an option.
                </div>
            @endif
        </form>
        @endif
    </div>
@endif
