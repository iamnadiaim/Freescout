@php
    $satisfactionRatingSetting = \Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();

    $satisfactionRatings = \Modules\PoliwangiSatisfaction\Models\SatisfactionRating::where('conversation_id', $conversation->id)
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




