@php
    $routeMailboxId = request()->route('id') ?: request()->route('mailbox_id');

    /*
     * Default kalau halaman /help atau belum pilih mailbox.
     */
    $footerText = '© {%year%} Poliwangi Portal';

    /*
     * Kalau sedang buka /help/{id}, pakai footer setting mailbox.
     */
    if (!empty($routeMailboxId) && !empty($mailbox)) {
        if (!empty($setting) && !empty($setting->footer)) {
            $footerText = $setting->footer;
        } else {
            $footerText = '© {%year%} {%mailbox.name%}';
        }
    }

    $mailboxName = !empty($mailbox) && !empty($mailbox->name)
        ? $mailbox->name
        : 'Poliwangi Portal';

    $footerText = str_replace('{%year%}', date('Y'), $footerText);
    $footerText = str_replace('{%mailbox.name%}', $mailboxName, $footerText);
@endphp

<style>
    .portal-footer-global {
        width: 100%;
        text-align: center;
        color: #a6b4c1;
        font-size: 14px;
        padding: 22px 15px 25px;
        margin-top: 25px;
    }

    .portal-footer-global a {
        color: #7f9db9;
        text-decoration: none;
    }

    .portal-footer-global a:hover {
        color: #0078d4;
        text-decoration: underline;
    }
</style>

<div class="portal-footer-global" id="portalFooterText">
    {{ $footerText }}
</div>
