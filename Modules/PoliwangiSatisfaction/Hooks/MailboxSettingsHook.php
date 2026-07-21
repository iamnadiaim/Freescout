<?php

namespace Modules\PoliwangiSatisfaction\Hooks;

class MailboxSettingsHook
{
    public static function register()
    {
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            echo view('poliwangisatisfaction::partials.mailbox_settings_menu', [
                'mailbox' => $mailbox,
            ])->render();
        });
    }
}
