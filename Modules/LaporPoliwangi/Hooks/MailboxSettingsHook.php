<?php

namespace Modules\LaporPoliwangi\Hooks;

class MailboxSettingsHook
{
    public static function register()
    {
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            echo view('laporpoliwangi::partials.mailbox_settings_menu', [
                'mailbox' => $mailbox,
            ])->render();
        });
    }
}
