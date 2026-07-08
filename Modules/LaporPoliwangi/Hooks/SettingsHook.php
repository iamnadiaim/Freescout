<?php

namespace Modules\LaporPoliwangi\Hooks;

use App\Mailbox;
use Modules\LaporPoliwangi\Models\NotificationChannel;
use Modules\LaporPoliwangi\Services\Notifications\NotificationSenderFactory;

class SettingsHook
{
    public static function register()
    {
        self::registerNotificationChannelsSection();
    }

    private static function registerNotificationChannelsSection()
    {
        \Eventy::addFilter('settings.sections', function ($sections) {
            $sections['notification_channels'] = [
                'title' => 'Notification Channels',
                'icon'  => 'send',
                'order' => 500,
            ];

            return $sections;
        });

        \Eventy::addFilter('settings.section_settings', function ($settings, $section) {
            if ($section === 'notification_channels') {
                return [
                    'notification_channels_enabled' => true,
                ];
            }

            return $settings;
        }, 20, 2);

        \Eventy::addFilter('settings.section_params', function ($params, $section) {
            if ($section === 'notification_channels') {
                return [
                    'template_vars' => [
                        'notificationChannels' => NotificationChannel::with('mailbox')
                            ->orderBy('id', 'desc')
                            ->get(),

                        'mailboxes' => Mailbox::orderBy('name', 'asc')
                            ->get(),

                        'supportedTypes' => app(NotificationSenderFactory::class)
                            ->supportedTypes(),
                    ],

                    'settings' => [],
                ];
            }

            return $params;
        }, 20, 2);
    }
}
