<?php

namespace Modules\LaporPoliwangi\Services\Notifications\Webhooks;

use App\Conversation;
use Modules\LaporPoliwangi\Services\Notifications\NotificationWebhookInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\LaporPoliwangi\Models\NotificationChannel;

class TelegramWebhookHandler implements NotificationWebhookInterface
{
    public function handle(Request $request)
    {
        $update = $request->all();

        if (empty($update['callback_query'])) {
            return response()->json([
                'ok'      => true,
                'message' => 'No callback query.',
            ]);
        }

        $callbackQuery = $update['callback_query'];

        $callbackId = isset($callbackQuery['id'])
            ? $callbackQuery['id']
            : null;

        $callbackData = isset($callbackQuery['data'])
            ? $callbackQuery['data']
            : null;

        $telegramMessage = isset($callbackQuery['message'])
            ? $callbackQuery['message']
            : [];

        $chatId = isset($telegramMessage['chat']['id'])
            ? (string) $telegramMessage['chat']['id']
            : null;

        $messageId = isset($telegramMessage['message_id'])
            ? $telegramMessage['message_id']
            : null;

        if (!$callbackData) {
            return response()->json([
                'ok'      => true,
                'message' => 'No callback data.',
            ]);
        }

        $parsed = $this->parseAction($callbackData);

        if (!$parsed) {
            $this->answerCallback(
                $callbackId,
                'Action tidak valid.',
                $chatId
            );

            return response()->json([
                'ok'      => true,
                'message' => 'Invalid callback data.',
            ]);
        }

        $conversation = Conversation::find(
            $parsed['conversation_id']
        );

        if (!$conversation) {
            $this->answerCallback(
                $callbackId,
                'Laporan tidak ditemukan.',
                $chatId
            );

            return response()->json([
                'ok'      => true,
                'message' => 'Conversation not found.',
            ]);
        }

        try {
            $statusText = $this->applyAction(
                $conversation,
                $parsed['action']
            );

            $this->answerCallback(
                $callbackId,
                'Status laporan diubah menjadi ' . $statusText . '.',
                $chatId
            );

            if ($chatId && $messageId) {
                $oldText = isset($telegramMessage['text'])
                    ? $telegramMessage['text']
                    : '📩 Laporan baru masuk';

                $oldText = preg_replace(
                    '/\n\nStatus laporan:.*$/s',
                    '',
                    $oldText
                );

                $this->editMessage(
                    $chatId,
                    $messageId,
                    $oldText . "\n\nStatus laporan: " . $statusText
                );
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Status updated.',
            ]);
        } catch (\Exception $e) {
            Log::error('Telegram webhook failed.', [
                'conversation_id' => $conversation->id,
                'action'          => $parsed['action'],
                'error'           => $e->getMessage(),
            ]);

            $this->answerCallback(
                $callbackId,
                'Gagal mengubah status laporan.',
                $chatId
            );

            return response()->json([
                'ok'      => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function parseAction($callbackData)
    {
        if (!preg_match(
            '/^(active|pending|close|spam)_conversation_([0-9]+)$/',
            $callbackData,
            $matches
        )) {
            return null;
        }

        return [
            'action'          => $matches[1],
            'conversation_id' => (int) $matches[2],
        ];
    }

    private function applyAction(
        Conversation $conversation,
        $action
    ) {
        switch ($action) {
            case 'active':
                $conversation->status = Conversation::STATUS_ACTIVE;
                $statusText = 'Aktif';
                break;

            case 'pending':
                $conversation->status = Conversation::STATUS_PENDING;
                $statusText = 'Pending';
                break;

            case 'close':
                $conversation->status = Conversation::STATUS_CLOSED;
                $statusText = 'Ditutup';
                break;

            case 'spam':
                $conversation->status = Conversation::STATUS_SPAM;
                $statusText = 'Spam';
                break;

            default:
                throw new \InvalidArgumentException(
                    'Action tidak dikenali.'
                );
        }

        $conversation->user_updated_at = now();
        $conversation->save();

        if (method_exists($conversation, 'updateFolder')) {
            $conversation->updateFolder();
        }

        if ($conversation->mailbox && method_exists($conversation->mailbox, 'updateFoldersCounters')) {
            $conversation->mailbox->updateFoldersCounters();
        }

        return $statusText;
    }

    private function answerCallback(
        $callbackId,
        $text,
        $chatId
    ) {
        if (!$callbackId) {
            return false;
        }

        $channel = $this->findChannelByChatId($chatId);

        if (!$channel) {
            return false;
        }

        $config = is_array($channel->config)
            ? $channel->config
            : [];

        if (empty($config['bot_token'])) {
            return false;
        }

        $url = 'https://api.telegram.org/bot'
            . $config['bot_token']
            . '/answerCallbackQuery';

        return $this->sendTelegramRequest($url, [
            'callback_query_id' => $callbackId,
            'text'              => $text,
            'show_alert'        => false,
        ]);
    }

    private function editMessage(
        $chatId,
        $messageId,
        $text
    ) {
        $channel = $this->findChannelByChatId($chatId);

        if (!$channel) {
            return false;
        }

        $config = is_array($channel->config)
            ? $channel->config
            : [];

        if (empty($config['bot_token'])) {
            return false;
        }

        $url = 'https://api.telegram.org/bot'
            . $config['bot_token']
            . '/editMessageText';

        return $this->sendTelegramRequest($url, [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $text,
        ]);
    }

    private function findChannelByChatId($chatId)
    {
        if (!$chatId) {
            return null;
        }

        $channels = NotificationChannel::where(
            'type',
            'telegram'
        )
            ->where('is_active', true)
            ->get();

        foreach ($channels as $channel) {
            $config = is_array($channel->config)
                ? $channel->config
                : [];

            if (
                isset($config['chat_id']) &&
                (string) $config['chat_id'] === (string) $chatId
            ) {
                return $channel;
            }
        }

        return null;
    }

    private function sendTelegramRequest(
        $url,
        array $payload
    ) {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => app()->environment('production'),
            CURLOPT_SSL_VERIFYHOST => app()->environment('production')
                ? 2
                : 0,
        ]);

        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($error) {
            Log::error('Telegram request error.', [
                'error' => $error,
            ]);

            return false;
        }

        $response = json_decode($result, true);

        return $httpCode >= 200
            && $httpCode < 300
            && is_array($response)
            && !empty($response['ok']);
    }
}
