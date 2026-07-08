@section('javascript')
    @parent
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
@endsection
