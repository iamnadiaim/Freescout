<?php

namespace Modules\LaporPoliwangi\Services\Notifications;

use InvalidArgumentException;

class NotificationSenderFactory
{
    public function make($type, array $config = [])
    {
        $drivers = config('notification_channels.drivers', []);

        if (!isset($drivers[$type])) {
            throw new InvalidArgumentException(
                'Tipe notification channel tidak didukung: ' . $type
            );
        }

        $senderClass = $drivers[$type]['sender'];

        if (!class_exists($senderClass)) {
            throw new InvalidArgumentException(
                'Sender class tidak ditemukan: ' . $senderClass
            );
        }

        $sender = new $senderClass($config);

        if (!$sender instanceof NotificationSenderInterface) {
            throw new InvalidArgumentException(
                $senderClass . ' harus mengimplementasikan NotificationSenderInterface.'
            );
        }

        return $sender;
    }

    public function supportedTypes()
    {
        return array_keys(
            config('notification_channels.drivers', [])
        );
    }

    public function rules($type)
    {
        $drivers = config('notification_channels.drivers', []);

        if (!isset($drivers[$type])) {
            return [];
        }

        return isset($drivers[$type]['rules'])
            ? $drivers[$type]['rules']
            : [];
    }
}
