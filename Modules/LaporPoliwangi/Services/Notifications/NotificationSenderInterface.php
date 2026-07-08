<?php

namespace Modules\LaporPoliwangi\Services\Notifications;

interface NotificationSenderInterface
{
    /**
     * Mengirim notifikasi.
     *
     * @param string|null $recipient
     * @param string      $message
     * @param array       $options
     *
     * @return array
     */
    public function send($recipient, $message, array $options = []);
}
