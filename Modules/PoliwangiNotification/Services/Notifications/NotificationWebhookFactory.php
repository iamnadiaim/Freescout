<?php

namespace Modules\PoliwangiNotification\Services\Notifications;

use InvalidArgumentException;

class NotificationWebhookFactory
{
    public function make($type)
    {
        $handlers = config(
            'notification_channels.webhooks',
            []
        );

        if (!isset($handlers[$type])) {
            throw new InvalidArgumentException(
                'Webhook handler tidak tersedia untuk tipe: ' . $type
            );
        }

        $handlerClass = $handlers[$type];

        if (!class_exists($handlerClass)) {
            throw new InvalidArgumentException(
                'Class webhook handler tidak ditemukan: '
                . $handlerClass
            );
        }

        $handler = app($handlerClass);

        if (!$handler instanceof NotificationWebhookInterface) {
            throw new InvalidArgumentException(
                $handlerClass
                . ' harus mengimplementasikan NotificationWebhookInterface.'
            );
        }

        return $handler;
    }
}
