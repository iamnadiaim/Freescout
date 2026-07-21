@php
    $user = Auth::user();
@endphp

@if ($user)
    @if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, \App\Mailbox::ACCESS_PERM_EDIT))
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/satisfaction-ratings*') ? 'active' : '' }}">
            <a href="{{ route('PoliwangiPortal.satisfaction_ratings.index', $mailbox->id) }}">
                <i class="glyphicon glyphicon-star"></i>
                {{ __('Satisfaction Ratings') }}
            </a>
        </li>
    @endif
@endif
