@php
    $user = Auth::user();
@endphp

@if ($user)
    @if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, \App\Mailbox::ACCESS_PERM_EDIT))
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/saved-replies*') ? 'active' : '' }}">
            <a href="{{ route('poliwangisavedreply.saved_replies', $mailbox->id) }}">
                <i class="glyphicon glyphicon-comment"></i>
                {{ __('Saved Replies') }}
            </a>
        </li>
    @endif
@endif
