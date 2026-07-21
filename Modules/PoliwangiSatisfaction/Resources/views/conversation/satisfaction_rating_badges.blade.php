@if (isset($rating) && $rating)
    @php
        $ratingClass = 'label-default';
        $ratingLabel = '';

        if ($rating->rating == 'great') {
            $ratingClass = 'label-success';
            $ratingLabel = 'GREAT RATING';
        } elseif ($rating->rating == 'okay') {
            $ratingClass = 'label-warning';
            $ratingLabel = 'OKAY RATING';
        } elseif ($rating->rating == 'not_good') {
            $ratingClass = 'label-danger';
            $ratingLabel = 'NOT GOOD RATING';
        }
    @endphp

    @if ($ratingLabel)
        <span class="label {{ $ratingClass }} satisfaction-rating-badge"
            data-lp-rating-badge="1"
            data-thread-id="{{ $rating->thread_id }}"
            @if (!empty($rating->comment))
                data-toggle="popover"
                data-placement="bottom"
                data-html="true"
                title="Rating Comment"
                data-content="{{ e($rating->comment) }}"
            @endif
            style="margin-left: 8px; vertical-align: middle;"
        >
            {{ $ratingLabel }}

            @if (!empty($rating->comment))
                &nbsp;<i class="glyphicon glyphicon-comment"></i>
            @endif
        </span>
    @endif
@endif
