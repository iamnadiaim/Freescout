<?php

namespace Modules\PoliwangiCustomField\Hooks;

class MailboxSettingsHook
{
    public static function register()
    {
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            echo view('poliwangicustomfield::partials.mailbox_settings_menu', [
                'mailbox' => $mailbox,
            ])->render();
        });
    }
}
