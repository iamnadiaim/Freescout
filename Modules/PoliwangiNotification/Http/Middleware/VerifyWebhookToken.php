<?php

namespace Modules\PoliwangiNotification\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\PoliwangiNotification\Models\NotificationChannel;

class VerifyWebhookToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $type = $request->route('type');

        if ($type === 'telegram') {
            return $this->verifyTelegram($request, $next);
        }

        // Jika ada provider lain di masa depan (misal WhatsApp),
        // tambahkan logika verifikasinya di sini.

        return $next($request);
    }

    /**
     * Verifikasi Secret Token untuk webhook Telegram.
     */
    private function verifyTelegram(Request $request, Closure $next)
    {
        $headerToken = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!$headerToken) {
            return response()->json([
                'ok'      => false,
                'message' => 'Unauthorized. Missing token.',
            ], 401);
        }

        $isValid = false;
        $channels = NotificationChannel::where('type', 'telegram')
            ->where('is_active', true)
            ->get();
        
        foreach ($channels as $channel) {
            $config = is_array($channel->config) ? $channel->config : [];
            if (!empty($config['bot_token'])) {
                // Kalkulasi Secret Token berdasarkan bot_token yang terdaftar
                $expectedToken = substr(hash('sha256', $config['bot_token']), 0, 32);
                if (hash_equals($expectedToken, $headerToken)) {
                    $isValid = true;
                    break;
                }
            }
        }

        if (!$isValid) {
            return response()->json([
                'ok'      => false,
                'message' => 'Unauthorized. Invalid token.',
            ], 401);
        }

        return $next($request);
    }
}
