<?php

namespace Modules\PoliwangiPortal\Hooks;

class MailboxSettingsHook
{
    public static function register()
    {
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            echo view('poliwangiportal::partials.mailbox_settings_menu', [
                'mailbox' => $mailbox,
            ])->render();
        });
    }
}
