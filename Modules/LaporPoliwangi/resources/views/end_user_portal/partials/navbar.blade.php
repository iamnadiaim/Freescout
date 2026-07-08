@php
    $navbarMailbox = isset($mailbox) && !empty($mailbox) ? $mailbox : null;

    /*
     * Login pelapor sekarang global.
     * Jadi session tidak lagi pakai end_user_portal_email_{mailbox_id}.
     */
    $email = $email ?? session('end_user_portal_email');

    /*
     * URL aktif untuk redirect setelah login/logout.
     */
    $currentUrl = url()->current();

    /*
     * Link utama.
     */
    $homeUrl = url('/help');

    $submitUrl = $navbarMailbox ? route('laporpoliwangi.end_user_portal.submit_ticket', $navbarMailbox->id) : url('/help');

    $myTicketUrl = route('laporpoliwangi.end_user_portal.my_ticket');

    $loginUrl = route('laporpoliwangi.end_user_portal.auth_select', [
        'redirect' => $currentUrl,
    ]);

    $logoutUrl = route('laporpoliwangi.end_user_portal.logout', [
        'redirect' => $homeUrl,
    ]);
@endphp

<style>
    .portal-navbar {
        position: sticky;
        top: 0;
        z-index: 9999;
        height: 64px;
        background: rgba(255, 255, 255, .94);
        backdrop-filter: blur(12px);
        border-bottom: 1px solid #e8edf3;
        color: #25384b;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 28px;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 12px;
        min-width: 0;
    }

    .brand-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        flex: 0 0 auto;
        text-decoration: none;
        background: transparent;
    }

    .brand-logo img {
        height: 38px;
        width: auto;
        display: block;
        object-fit: contain;
    }

    .brand-text {
        line-height: 1.15;
        min-width: 0;
    }

    .brand-title {
        font-size: 15px;
        font-weight: 700;
        color: #102a43;
        white-space: nowrap;
    }

    .brand-subtitle {
        font-size: 12px;
        color: #7b8794;
        margin-top: 3px;
        white-space: nowrap;
    }

    .portal-menu {
        display: flex;
        align-items: center;
        gap: 8px;
        height: 100%;
    }

    .portal-menu a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 34px;
        padding: 0 14px;
        border-radius: 999px;
        color: #25384b;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        transition: all .18s ease;
        white-space: nowrap;
    }

    .portal-menu a.active,
    .portal-menu a:hover {
        background: #0a84df;
        color: #ffffff;
        text-decoration: none;
    }

    .portal-login-link {
        border: 1px solid #d7e0ea;
        background: #ffffff;
        color: #25384b !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
    }

    .portal-login-link:hover,
    .portal-login-link.active {
        border-color: #0a84df;
        color: #0a84df !important;
        background: #ffffff !important;
        text-decoration: none;
    }

    .portal-login-link:hover .portal-user-icon,
    .portal-login-link.active .portal-user-icon {
        color: #0a84df;
    }

    .portal-user-dropdown {
        position: relative;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .portal-user-btn {
        min-height: 34px;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 0 14px;
        border: 1px solid #d7e0ea;
        border-radius: 999px;
        background: #ffffff;
        color: #25384b;
        cursor: pointer;
        font-size: 13px;
        font-weight: 700;
        user-select: none;
        white-space: nowrap;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .04);
        transition: all .18s ease;
    }

    .portal-user-btn:hover,
    .portal-user-dropdown.open .portal-user-btn {
        border-color: #0a84df;
        color: #0a84df;
    }

    .portal-user-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: currentColor;
        line-height: 1;
    }

    .portal-user-icon svg {
        display: block;
    }

    .portal-user-name {
        max-width: 230px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .portal-arrow {
        font-size: 10px;
        margin-left: 2px;
        transition: transform 0.2s ease;
    }

    .portal-user-dropdown.open .portal-arrow {
        transform: rotate(180deg);
    }

    .portal-dropdown-menu {
        position: absolute;
        top: 48px;
        right: 0;
        min-width: 190px;
        background: #ffffff;
        border: 1px solid #e5eaf0;
        border-radius: 12px;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .14);
        display: none;
        z-index: 10000;
        overflow: hidden;
        padding: 8px;
    }

    .portal-dropdown-menu a {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-height: 38px;
        padding: 8px 14px;
        color: #344054;
        background: #f8fafc;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: 1px solid #e4e7ec;
        border-radius: 8px;
        transition: all 0.2s ease;
    }

    .portal-dropdown-menu a i {
        font-size: 14px;
        color: #667085;
    }

    .portal-dropdown-menu a:hover {
        background: #fff1f1;
        border-color: #f5b5b5;
        color: #c62828;
        text-decoration: none;
    }

    .portal-dropdown-menu a:hover i {
        color: #c62828;
    }

    .portal-user-dropdown.open .portal-dropdown-menu {
        display: block;
    }

    @media (max-width: 700px) {
        .portal-navbar {
            height: auto;
            padding: 14px;
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .brand-logo img {
            height: 32px;
        }

        .brand-title {
            font-size: 14px;
        }

        .brand-subtitle {
            display: none;
        }

        .portal-menu {
            width: 100%;
            height: auto;
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .portal-menu a {
            white-space: nowrap;
        }

        .portal-user-dropdown {
            height: auto;
        }

        .portal-user-name {
            max-width: 160px;
        }

        .portal-dropdown-menu {
            top: 42px;
            right: auto;
            left: 0;
        }
    }
</style>

<div class="portal-navbar">
    <div class="brand">
        <a href="{{ $homeUrl }}" class="brand-logo">
            <img src="{{ asset('img/logo_poliwangi.png') }}" alt="Logo Poliwangi">
        </a>

        <div class="brand-text">
            <div class="brand-title">Lapor Poliwangi</div>
            <div class="brand-subtitle">Politeknik Negeri Banyuwangi</div>
        </div>
    </div>

    <div class="portal-menu">
        <a href="{{ $submitUrl }}"
            class="{{ request()->routeIs('laporpoliwangi.end_user_portal.submit_ticket') ? 'active' : '' }}">
            Submit Ticket
        </a>

        <a href="{{ $myTicketUrl }}"
            class="{{ request()->routeIs('laporpoliwangi.end_user_portal.my_ticket') ? 'active' : '' }}">
            Cek Status
        </a>

        @if (!empty($email))
            <div class="portal-user-dropdown" id="portalUserDropdown">
                <div class="portal-user-btn" onclick="togglePortalUserDropdown()">
                    <span class="portal-user-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5
                            2.239-5 5 2.239 5 5 5zm0 2c-3.314
                            0-10 1.657-10 5v3h20v-3c0-3.343-6.686-5-10-5z" />
                        </svg>
                    </span>

                    <span class="portal-user-name">{{ $email }}</span>
                    <span class="portal-arrow">▼</span>
                </div>

                <div class="portal-dropdown-menu">
                    <a href="{{ $logoutUrl }}">
                        <i class="glyphicon glyphicon-log-out"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        @else
            <a href="{{ $loginUrl }}"
                class="portal-login-link {{ request()->routeIs('laporpoliwangi.end_user_portal.login_end_user') ? 'active' : '' }}">

                <span class="portal-user-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path
                            d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm0 2c-3.33 0-10 1.67-10 5v3h20v-3c0-3.33-6.67-5-10-5z">
                        </path>
                    </svg>
                </span>

                <span>Login</span>
            </a>
        @endif
    </div>
</div>

<script>
    function togglePortalUserDropdown() {
        var dropdown = document.getElementById('portalUserDropdown');

        if (dropdown) {
            dropdown.classList.toggle('open');
        }
    }

    document.addEventListener('click', function(event) {
        var dropdown = document.getElementById('portalUserDropdown');

        if (!dropdown) {
            return;
        }

        if (!dropdown.contains(event.target)) {
            dropdown.classList.remove('open');
        }
    });
</script>
