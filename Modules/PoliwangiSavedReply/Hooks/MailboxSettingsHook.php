<?php

namespace Modules\PoliwangiSavedReply\Hooks;

class MailboxSettingsHook
{
    public static function register()
    {
        \Eventy::addAction('mailboxes.settings.menu', function ($mailbox) {
            echo view('poliwangisavedreply::partials.mailbox_settings_menu', [
                'mailbox' => $mailbox,
            ])->render();
        });
    }
}
