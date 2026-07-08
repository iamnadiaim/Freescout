<?php

namespace Modules\LaporPoliwangi\Services\Notifications;

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
