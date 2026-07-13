@extends('layouts.app')

@section('title', 'Satisfaction Ratings Report')

@section('sidebar')
    @include('partials/sidebar_menu_toggle')
    @include('mailboxes/sidebar_menu')
@endsection

@section('content')
    <div class="container satisfaction-report-page">

        {{-- HEADER --}}
        <div class="row">
            <div class="col-md-12">
                <div class="satisfaction-report-header">
                    <div>
                        <h1 class="page-heading satisfaction-page-title">
                            Satisfaction Ratings Report
                        </h1>

                        <p class="satisfaction-report-subtitle">
                            View feedback submitted by end users for this mailbox.
                        </p>
                    </div>

                    <div class="satisfaction-report-actions">
                        <a href="{{ route('laporpoliwangi.satisfaction_ratings.index', $mailbox->id) }}"
                            class="btn btn-default">
                            <i class="glyphicon glyphicon-cog"></i>
                            Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY --}}
        @php
            $totalRatings = $ratings->total();

            $greatCount = \Modules\LaporPoliwangi\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                ->where('rating', 'great')
                ->count();

            $okayCount = \Modules\LaporPoliwangi\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                ->where('rating', 'okay')
                ->count();

            $notGoodCount = \Modules\LaporPoliwangi\Models\SatisfactionRating::where('mailbox_id', $mailbox->id)
                ->where('rating', 'not_good')
                ->count();
        @endphp

        <div class="row satisfaction-summary-row">
            <div class="col-sm-3">
                <div class="satisfaction-summary-card">
                    <div class="satisfaction-summary-label">Total Ratings</div>
                    <div class="satisfaction-summary-value">{{ $totalRatings }}</div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="satisfaction-summary-card">
                    <div class="satisfaction-summary-label">Great</div>
                    <div class="satisfaction-summary-value">😊 {{ $greatCount }}</div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="satisfaction-summary-card">
                    <div class="satisfaction-summary-label">Okay</div>
                    <div class="satisfaction-summary-value">😐 {{ $okayCount }}</div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="satisfaction-summary-card">
                    <div class="satisfaction-summary-label">Not Good</div>
                    <div class="satisfaction-summary-value">☹️ {{ $notGoodCount }}</div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="satisfaction-report-card">
            <div class="satisfaction-report-card-header">
                <h3>Rating Details</h3>
            </div>

            @if ($ratings->count())
                <div class="table-responsive">
                    <table class="table table-striped table-hover satisfaction-rating-table">
                        <thead>
                            <tr>
                                <th style="width:60px;">No</th>
                                <th>Customer</th>
                                <th>Ticket</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th style="width:150px;">Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($ratings as $index => $rating)
                                @php
                                    $number = ($ratings->currentPage() - 1) * $ratings->perPage() + $index + 1;

                                    $ratingLabel = 'Unknown';
                                    $ratingEmoji = '⭐';
                                    $ratingClass = 'rating-badge-default';

                                    if ($rating->rating == 'great') {
                                        $ratingLabel = 'Great';
                                        $ratingEmoji = '😊';
                                        $ratingClass = 'rating-badge-great';
                                    } elseif ($rating->rating == 'okay') {
                                        $ratingLabel = 'Okay';
                                        $ratingEmoji = '😐';
                                        $ratingClass = 'rating-badge-okay';
                                    } elseif ($rating->rating == 'not_good') {
                                        $ratingLabel = 'Not Good';
                                        $ratingEmoji = '☹️';
                                        $ratingClass = 'rating-badge-not-good';
                                    }

                                    $customerName = '-';

                                    if ($rating->customer) {
                                        $customerName = trim(
                                            (string) $rating->customer->first_name . ' ' . (string) $rating->customer->last_name
                                        );

                                        if ($customerName == '') {
                                            $customerName = '-';
                                        }
                                    }

                                    $ticketSubject = $rating->conversation
                                        ? ($rating->conversation->subject ?: 'No Subject')
                                        : 'Ticket not found';
                                @endphp

                                <tr>
                                    <td>{{ $number }}</td>

                                    <td>
                                        <div class="rating-customer-name">
                                            {{ $customerName }}
                                        </div>

                                        <div class="rating-customer-email">
                                            {{ $rating->email ?: '-' }}
                                        </div>
                                    </td>

                                    <td>
                                        @if ($rating->conversation)
                                            <a href="{{ route('conversations.view', ['id' => $rating->conversation->id]) }}"
                                                target="_blank">
                                                {{ $ticketSubject }}
                                            </a>

                                            <div class="rating-ticket-meta">
                                                Conversation #{{ $rating->conversation->id }}
                                            </div>
                                        @else
                                            <span class="text-muted">
                                                {{ $ticketSubject }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="rating-badge {{ $ratingClass }}">
                                            {{ $ratingEmoji }} {{ $ratingLabel }}
                                        </span>
                                    </td>

                                    <td>
                                        @if ($rating->comment)
                                            <div class="rating-comment">
                                                {{ $rating->comment }}
                                            </div>
                                        @else
                                            <span class="text-muted">No comment</span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $rating->created_at ? $rating->created_at->format('M d, Y H:i') : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="satisfaction-pagination">
                    {{ $ratings->links() }}
                </div>
            @else
                <div class="satisfaction-empty-state">
                    <div class="satisfaction-empty-icon">
                        <i class="glyphicon glyphicon-stats"></i>
                    </div>

                    <h3>No ratings yet</h3>

                    <p>
                        Ratings submitted by end users will appear here.
                    </p>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('stylesheets')
    @parent
    <link href="{{ asset('css/satisfaction_rating.css') }}" rel="stylesheet">
@endsection
