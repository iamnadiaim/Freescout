@php
    $user = Auth::user();
@endphp

@if ($user)

    {{-- Custom Fields --}}
    @if ($user->isAdmin())
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/custom-fields*') ? 'active' : '' }}">
            <a href="{{ route('laporpoliwangi.custom_fields', $mailbox->id) }}">
                <i class="glyphicon glyphicon-th-list"></i>
                {{ __('Custom Fields') }}
            </a>
        </li>
    @endif

    {{-- Saved Replies --}}
    @if ($user->isAdmin())
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/saved-replies*') ? 'active' : '' }}">
            <a href="{{ route('laporpoliwangi.saved_replies', $mailbox->id) }}">
                <i class="glyphicon glyphicon-save"></i>
                {{ __('Saved Replies') }}
            </a>
        </li>
    @endif

    {{-- Satisfaction Ratings --}}
    @if ($user->isAdmin() ||
            (defined('\App\Mailbox::ACCESS_PERM_SATISFACTION_RATINGS') &&
                $user->hasManageMailboxPermission($mailbox->id, \App\Mailbox::ACCESS_PERM_SATISFACTION_RATINGS)))
        <li class="{{
            request()->is('lapor-poliwangi/mailboxes/*/satisfaction-ratings*')
                ? 'active'
                : ''
        }}">
            <a href="{{ route('laporpoliwangi.satisfaction_ratings.index', ['mailbox_id' => $mailbox->id]) }}">
                <i class="glyphicon glyphicon-thumbs-up"></i>
                {{ __('Sat. Ratings') }}
            </a>
        </li>
    @endif

    {{-- End-User Portal --}}
    @if ($user->isAdmin())
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/end-user-portal*') ? 'active' : '' }}">
            <a href="{{ Route::has('laporpoliwangi.end_user_portal.setting')
                ? route('laporpoliwangi.end_user_portal.setting', $mailbox->id)
                : '#' }}">
                <i class="glyphicon glyphicon-phone"></i>
                {{ __('End-User Portal') }}
            </a>
        </li>
    @endif

@endif
