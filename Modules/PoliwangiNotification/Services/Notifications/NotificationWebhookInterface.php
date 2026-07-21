<?php

namespace Modules\PoliwangiNotification\Services\Notifications;

use Illuminate\Http\Request;

interface NotificationWebhookInterface
{
    /**
     * Memproses webhook dari platform notifikasi.
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function handle(Request $request);
}
