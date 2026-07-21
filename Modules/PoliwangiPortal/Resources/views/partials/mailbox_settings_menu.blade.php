@php
    $user = Auth::user();
@endphp

@if ($user)






    {{-- End-User Portal --}}
    @if ($user->isAdmin() || $user->hasManageMailboxPermission($mailbox->id, \App\Mailbox::ACCESS_PERM_EDIT))
        <li class="{{ request()->is('lapor-poliwangi/mailboxes/*/end-user-portal*') ? 'active' : '' }}">
            <a href="{{ Route::has('PoliwangiPortal.end_user_portal.setting')
                ? route('PoliwangiPortal.end_user_portal.setting', $mailbox->id)
                : '#' }}">
                <i class="glyphicon glyphicon-phone"></i>
                {{ __('End-User Portal') }}
            </a>
        </li>
    @endif

@endif
