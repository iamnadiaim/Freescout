<?php

namespace Modules\PoliwangiNotification\Services\Notifications\Senders;

use Modules\PoliwangiNotification\Services\Notifications\NotificationSenderInterface;
use Modules\PoliwangiNotification\Services\Notifications\NotificationFieldHelpInterface;
use Exception;
use Illuminate\Support\Facades\Log;

class TelegramSender implements NotificationSenderInterface, NotificationFieldHelpInterface
{
    /**
     * Konfigurasi notification channel.
     *
     * @var array
     */
    private $config;

    /**
     * Membuat Telegram sender.
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Mengirim pesan beserta attachment Telegram.
     */
    public function send(
        $recipient,
        $message,
        array $options = []
    ) {
        $botToken = isset($this->config['bot_token'])
            ? trim((string) $this->config['bot_token'])
            : '';

        $chatId = $recipient
            ? trim((string) $recipient)
            : (
                isset($this->config['chat_id'])
                ? trim((string) $this->config['chat_id'])
                : ''
            );

        $threadId = isset($options['thread_id'])
            ? trim((string) $options['thread_id'])
            : (
                isset($this->config['thread_id'])
                ? trim((string) $this->config['thread_id'])
                : ''
            );

        if ($botToken === '' || $chatId === '') {
            throw new Exception(
                'Konfigurasi Telegram tidak lengkap. '
                    . 'Bot Token dan Chat ID wajib diisi.'
            );
        }

        /*
         * Kirim pesan utama.
         */
        $messageResult = $this->sendMessage(
            $botToken,
            $chatId,
            $threadId,
            (string) $message,
            $options
        );

        /*
         * Kirim attachment setelah pesan utama berhasil.
         */
        $attachmentResults = $this->sendAttachments(
            $botToken,
            $chatId,
            $threadId,
            $options
        );

        return [
            'success' => true,
            'channel_type' => 'telegram',
            'message_response' => $messageResult,
            'attachment_responses' => $attachmentResults,
        ];
    }

    /**
     * Mengirim pesan teks Telegram.
     */
    private function sendMessage(
        $botToken,
        $chatId,
        $threadId,
        $message,
        array $options
    ) {
        $url = 'https://api.telegram.org/bot'
            . $botToken
            . '/sendMessage';

        $payload = [
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => isset($options['parse_mode'])
                ? $options['parse_mode']
                : 'HTML',
        ];

        if ($threadId !== '') {
            $payload['message_thread_id'] = $threadId;
        }

        /*
         * Prioritaskan reply_markup yang sudah diberikan langsung.
         */
        if (!empty($options['reply_markup'])) {
            $payload['reply_markup'] = is_string(
                $options['reply_markup']
            )
                ? $options['reply_markup']
                : json_encode(
                    $options['reply_markup'],
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                );
        } elseif (!empty($options['actions'])) {
            /*
             * Ubah action umum menjadi inline keyboard Telegram.
             */
            $replyMarkup = $this->buildReplyMarkup(
                $options['actions']
            );

            if ($replyMarkup) {
                $payload['reply_markup'] = json_encode(
                    $replyMarkup,
                    JSON_UNESCAPED_UNICODE
                        | JSON_UNESCAPED_SLASHES
                );
            }
        }

        return $this->post(
            $url,
            $payload,
            false,
            20
        );
    }

    /**
     * Mengirim semua attachment pada options.
     */
    private function sendAttachments(
        $botToken,
        $chatId,
        $threadId,
        array $options
    ) {
        $attachments = isset($options['attachments'])
            && is_array($options['attachments'])
            ? $options['attachments']
            : [];

        if (empty($attachments)) {
            return [];
        }

        $results = [];

        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                Log::warning(
                    'Format attachment Telegram tidak valid.',
                    [
                        'attachment' => $attachment,
                    ]
                );

                continue;
            }

            try {
                $result = $this->sendAttachment(
                    $botToken,
                    $chatId,
                    $threadId,
                    $attachment,
                    $options
                );

                if ($result !== null) {
                    $results[] = $result;
                }
            } catch (\Throwable $e) {
                /*
                 * Satu attachment gagal tidak menghentikan
                 * pengiriman attachment lainnya.
                 */
                Log::error(
                    'Pengiriman attachment Telegram gagal.',
                    [
                        'name' => isset($attachment['name'])
                            ? $attachment['name']
                            : null,

                        'path' => isset($attachment['path'])
                            ? $attachment['path']
                            : null,

                        'error' => $e->getMessage(),
                    ]
                );
            }
        }

        return $results;
    }

    /**
     * Mengirim satu attachment melalui sendDocument.
     */
    private function sendAttachment(
        $botToken,
        $chatId,
        $threadId,
        array $attachment,
        array $options
    ) {
        $path = isset($attachment['path'])
            ? trim((string) $attachment['path'])
            : '';

        $name = isset($attachment['name'])
            ? trim((string) $attachment['name'])
            : '';

        $mimeType = isset($attachment['mime_type'])
            ? trim((string) $attachment['mime_type'])
            : 'application/octet-stream';

        if ($path === '' || !is_file($path)) {
            Log::warning(
                'File attachment Telegram tidak ditemukan.',
                [
                    'path' => $path,
                    'name' => $name,
                ]
            );

            return null;
        }

        if ($name === '') {
            $name = basename($path);
        }

        if ($mimeType === '') {
            $mimeType = 'application/octet-stream';
        }

        $url = 'https://api.telegram.org/bot'
            . $botToken
            . '/sendDocument';

        $payload = [
            'chat_id' => $chatId,

            'document' => new \CURLFile(
                $path,
                $mimeType,
                $name
            ),
        ];

        if ($threadId !== '') {
            $payload['message_thread_id'] = $threadId;
        }

        $subject = isset($options['subject'])
            ? trim((string) $options['subject'])
            : '';

        if ($subject !== '') {
            /*
             * Caption Telegram maksimal 1024 karakter.
             */
            $caption = '📎 Lampiran laporan: ' . $subject;

            $payload['caption'] = mb_substr(
                $caption,
                0,
                1024
            );
        }

        /*
         * Upload file wajib multipart.
         * Jangan memakai http_build_query().
         */
        $result = $this->post(
            $url,
            $payload,
            true,
            60
        );

        /*
         * Hapus hanya file yang memang diberi tanda sementara.
         * File permanen FreeScout tidak boleh dihapus.
         */
        if (
            !empty($attachment['temporary'])
            && is_file($path)
        ) {
            @unlink($path);
        }

        return $result;
    }

    /**
     * Membentuk inline keyboard Telegram dari action umum.
     */
    private function buildReplyMarkup($actions)
    {
        if (!is_array($actions) || empty($actions)) {
            return null;
        }

        $keyboard = [];

        foreach ($actions as $action) {
            if (
                !is_array($action)
                || empty($action['label'])
                || empty($action['type'])
            ) {
                continue;
            }

            $button = [
                'text' => (string) $action['label'],
            ];

            if (
                $action['type'] === 'url'
                && !empty($action['url'])
            ) {
                $button['url'] = (string) $action['url'];
            } elseif (
                $action['type'] === 'callback'
                && !empty($action['value'])
            ) {
                $button['callback_data'] = mb_substr(
                    (string) $action['value'],
                    0,
                    64
                );
            } else {
                continue;
            }

            /*
             * Satu action dibuat menjadi satu baris tombol.
             */
            $keyboard[] = [$button];
        }

        if (empty($keyboard)) {
            return null;
        }

        return [
            'inline_keyboard' => $keyboard,
        ];
    }

    /**
     * Mengirim POST request ke Telegram Bot API.
     *
     * $multipart:
     * - false untuk pesan teks;
     * - true untuk upload file.
     */
    private function post(
        $url,
        array $payload,
        $multipart = false,
        $timeout = 20
    ) {
        if (!function_exists('curl_init')) {
            throw new Exception(
                'Ekstensi PHP cURL belum aktif.'
            );
        }
//menginisialisasi curl
        $ch = curl_init();

        if ($ch === false) {
            throw new Exception(
                'Gagal menginisialisasi cURL Telegram.'
            );
        }

        /*
         * Pesan teks boleh menggunakan urlencoded.
         * Upload file harus mengirim array asli agar CURLFile diproses.
         */
        $postFields = $multipart
            ? $payload
            : http_build_query($payload);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeout,

            CURLOPT_SSL_VERIFYPEER =>
            app()->environment('production'),

            CURLOPT_SSL_VERIFYHOST =>
            app()->environment('production')
                ? 2
                : 0,
        ]);

        $result = curl_exec($ch);

        $error = curl_error($ch);

        $httpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        curl_close($ch);

        if ($result === false || $error) {
            throw new Exception(
                'Telegram CURL Error: '
                    . ($error ?: 'Unknown cURL error.')
            );
        }

        $response = json_decode(
            $result,
            true
        );

        if (
            $httpCode < 200
            || $httpCode >= 300
            || !is_array($response)
            || empty($response['ok'])
        ) {
            throw new Exception(
                'Telegram API Error: ' . $result
            );
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'response' => $response,
        ];
    }

    public static function fieldHelps()
    {
        return [
            'bot_token' => [
                'label' => 'Bot API Token',
                'help' => 'Buka Telegram dan cari @BotFather. Buat bot baru menggunakan perintah /newbot, kemudian salin Bot API Token yang diberikan ke kolom ini.',
                'link' => 'https://t.me/BotFather',
                'link_label' => 'Buka @BotFather',
            ],

            'chat_id' => [
                'label' => 'Chat ID',
                'help' => 'Kirim pesan /start ke bot Telegram, lalu buka URL getUpdates untuk mengambil nilai chat.id.',
                'example' => 'https://api.telegram.org/botTOKEN_BOT/getUpdates',
            ],

            'thread_id' => [
                'label' => 'Thread ID',
                'help' => 'Opsional. Diisi hanya jika menggunakan grup Telegram dengan fitur forum/topic. Jika tidak memakai topic, kosongkan.',
            ],
        ];
    }
}
