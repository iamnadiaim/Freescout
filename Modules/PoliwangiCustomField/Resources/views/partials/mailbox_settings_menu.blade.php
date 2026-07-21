@php
    $user = Auth::user();
@endphp

@if ($user)
    {{-- Custom Fields --}}
    @if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, \App\Mailbox::ACCESS_PERM_EDIT))
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/custom-fields*') ? 'active' : '' }}">
            <a href="{{ route('PoliwangiPortal.custom_fields', $mailbox->id) }}">
                <i class="glyphicon glyphicon-th-list"></i>
                {{ __('Custom Fields') }}
            </a>
        </li>
    @endif
@endif
