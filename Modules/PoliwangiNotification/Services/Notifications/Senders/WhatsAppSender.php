<?php

namespace Modules\PoliwangiNotification\Services\Notifications\Senders;

use Modules\PoliwangiNotification\Services\Notifications\NotificationSenderInterface;
use Modules\PoliwangiNotification\Services\Notifications\NotificationFieldHelpInterface;
use Exception;

class WhatsAppSender implements NotificationSenderInterface, NotificationFieldHelpInterface
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send($recipient, $message, array $options = [])
    {
        $apiUrl = isset($this->config['api_url'])
            ? trim($this->config['api_url'])
            : null;

        $apiToken = isset($this->config['api_token'])
            ? trim($this->config['api_token'])
            : null;

        $target = $recipient ?: (
            isset($this->config['phone_number'])
            ? trim($this->config['phone_number'])
            : null
        );

        if (!$apiUrl) {
            throw new Exception('URL API WhatsApp belum diisi.');
        }

        if (!$apiToken) {
            throw new Exception('Token API WhatsApp belum diisi.');
        }

        if (!$target) {
            throw new Exception('Nomor tujuan WhatsApp belum diisi.');
        }

        $target = $this->normalizePhoneNumber($target);

        $targetField = isset($this->config['target_field'])
            ? $this->config['target_field']
            : 'target';

        $messageField = isset($this->config['message_field'])
            ? $this->config['message_field']
            : 'message';

        $payload = [
            $targetField  => $target,
            $messageField => $message,
        ];

        if (
            isset($options['attachments'])
            && is_array($options['attachments'])
            && count($options['attachments']) > 0
        ) {
            $firstAttachment = $options['attachments'][0];

            if (
                isset($firstAttachment['path'])
                && is_file($firstAttachment['path'])
            ) {
                $payload['file'] = new \CURLFile(
                    $firstAttachment['path'],
                    isset($firstAttachment['mime_type']) ? $firstAttachment['mime_type'] : 'application/octet-stream',
                    isset($firstAttachment['name']) ? $firstAttachment['name'] : basename($firstAttachment['path'])
                );
            }
        }

        if (
            isset($this->config['extra_payload'])
            && is_array($this->config['extra_payload'])
        ) {
            $payload = array_merge(
                $payload,
                $this->config['extra_payload']
            );
        }

        if (
            isset($options['payload'])
            && is_array($options['payload'])
        ) {
            $payload = array_merge(
                $payload,
                $options['payload']
            );
        }

        $result = $this->sendRequest(
            $apiUrl,
            $apiToken,
            $payload
        );

        if (
            isset($options['attachments'])
            && is_array($options['attachments'])
            && count($options['attachments']) > 1
        ) {
            foreach (array_slice($options['attachments'], 1) as $attachment) {
                if (
                    !isset($attachment['path'])
                    || !is_file($attachment['path'])
                ) {
                    continue;
                }

                $filePayload = [
                    $targetField  => $target,
                    $messageField => isset($attachment['name'])
                        ? 'Lampiran: ' . $attachment['name']
                        : 'Lampiran laporan',
                    'file' => new \CURLFile(
                        $attachment['path'],
                        isset($attachment['mime_type']) ? $attachment['mime_type'] : 'application/octet-stream',
                        isset($attachment['name']) ? $attachment['name'] : basename($attachment['path'])
                    ),
                ];

                $this->sendRequest(
                    $apiUrl,
                    $apiToken,
                    $filePayload
                );
            }
        }

        return $result;
    }

    private function sendRequest(
        $apiUrl,
        $apiToken,
        array $payload
    ) {
        $authType = isset($this->config['auth_type'])
            ? $this->config['auth_type']
            : 'token';

        $headers = [
            'Accept: application/json',
        ];

        if ($authType === 'bearer') {
            $headers[] = 'Authorization: Bearer ' . $apiToken;
        } else {
            // Fonnte memakai Authorization: TOKEN
            $headers[] = 'Authorization: ' . $apiToken;
        }

        $contentType = isset($this->config['content_type'])
            ? $this->config['content_type']
            : 'form';

        $hasFile = false;

        foreach ($payload as $payloadValue) {
            if ($payloadValue instanceof \CURLFile) {
                $hasFile = true;
                break;
            }
        }

        /*
 * Kalau ada file, wajib kirim sebagai multipart/form-data.
 * Jangan json_encode karena CURLFile tidak akan terbaca sebagai file.
 */
        if ($contentType === 'json' && !$hasFile) {
            $headers[] = 'Content-Type: application/json';
            $requestBody = json_encode($payload);
        } else {
            $requestBody = $payload;
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $apiUrl,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $requestBody,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,

            // Untuk lokal. Di production sebaiknya verifikasi SSL aktif.
            CURLOPT_SSL_VERIFYPEER => app()->environment('production'),
            CURLOPT_SSL_VERIFYHOST => app()->environment('production') ? 2 : 0,
        ]);

        $result = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($curlError) {
            throw new Exception(
                'WhatsApp CURL Error: ' . $curlError
            );
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new Exception(
                'WhatsApp API Error ' . $httpCode . ': ' . $result
            );
        }

        $response = json_decode($result, true);

        if (
            is_array($response)
            && array_key_exists('status', $response)
            && !$response['status']
        ) {
            throw new Exception(
                'Provider WhatsApp gagal mengirim: ' . $result
            );
        }

        return [
            'success'      => true,
            'channel_type' => 'whatsapp',
            'response'     => $response ?: $result,
        ];
    }

    private function normalizePhoneNumber($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);

        $countryCode = isset($this->config['country_code'])
            ? $this->config['country_code']
            : '62';

        if (strpos($number, '0') === 0) {
            return $countryCode . substr($number, 1);
        }

        return $number;
    }

    public static function fieldHelps()
    {
        return [
            'api_url' => [
                'label' => 'URL API WhatsApp',
                'help' => 'Isi URL API dari provider WhatsApp gateway yang digunakan.',
                'example' => 'https://domain-provider.com/api/send',
            ],

            'api_token' => [
                'label' => 'API Token',
                'help' => 'Isi token API dari dashboard provider WhatsApp gateway. Biasanya ada di menu API Key atau Developer.',
            ],

            'phone_number' => [
                'label' => 'Nomor Tujuan',
                'help' => 'Isi nomor tujuan notifikasi dengan format internasional tanpa tanda +.',
                'example' => '6281234567890',
            ],

            'auth_type' => [
                'label' => 'Auth Type',
                'help' => 'Pilih jenis autentikasi sesuai dokumentasi provider. Umumnya menggunakan Bearer Token.',
            ],

            'content_type' => [
                'label' => 'Content Type',
                'help' => 'Pilih format request sesuai dokumentasi provider. Umumnya JSON.',
            ],

            'target_field' => [
                'label' => 'Target Field',
                'help' => 'Nama parameter untuk nomor tujuan sesuai dokumentasi API provider.',
                'example' => 'target, phone, number',
            ],

            'message_field' => [
                'label' => 'Message Field',
                'help' => 'Nama parameter untuk isi pesan sesuai dokumentasi API provider.',
                'example' => 'message, text',
            ],

            'country_code' => [
                'label' => 'Kode Negara',
                'help' => 'Kode negara default untuk nomor lokal. Untuk Indonesia gunakan 62.',
                'example' => '62',
            ],
        ];
    }
}
