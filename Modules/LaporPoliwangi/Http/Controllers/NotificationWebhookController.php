<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookFactory;

class NotificationWebhookController extends Controller
{
    private $factory;

    public function __construct(
        NotificationWebhookFactory $factory
    ) {
        $this->factory = $factory;
    }

    /**
     * Menerima webhook berdasarkan tipe channel.
     *
     * Contoh:
     * /notification/webhook/telegram
     * /notification/webhook/whatsapp
     */
    public function handle(Request $request, $type)
    {
        try {
            $handler = $this->factory->make($type);

            return $handler->handle($request);
        } catch (\Exception $e) {
            Log::error('Notification webhook failed.', [
                'type'  => $type,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
