<?php

return [

    'drivers' => [

        'telegram' => [
            'label' => 'Telegram',
            'icon'  => 'glyphicon-send',

            'sender' =>
            Modules\PoliwangiNotification\Services\Notifications\Senders\TelegramSender::class,

            'rules' => [
                'bot_token' => 'required|string|max:255',
                'chat_id'   => 'required|string|max:100',
                'thread_id' => 'nullable|string|max:100',
            ],

            'fields' => [
                'bot_token' => [
                    'label'       => 'Bot Token',
                    'type'        => 'password',
                    'required'    => true,
                    'secure'      => true,
                    'maxlength'   => 255,
                    'placeholder' => '123456789:AAxxxxxxxxxxxxxxxx',
                    'help'        => 'Token bot Telegram dari BotFather.',
                ],

                'chat_id' => [
                    'label'       => 'Chat ID',
                    'type'        => 'text',
                    'required'    => true,
                    'maxlength'   => 100,
                    'placeholder' => 'Contoh: -1001234567890',
                ],

                'thread_id' => [
                    'label'       => 'Thread ID',
                    'type'        => 'text',
                    'required'    => false,
                    'maxlength'   => 100,
                    'placeholder' => 'Opsional untuk Telegram Forum Topic',
                    'help'        => 'Kosongkan jika bot tidak menggunakan topic atau thread Telegram.',
                ],
            ],
        ],

        'whatsapp' => [
            'label' => 'WhatsApp',
            'icon'  => 'svg',

            'icon_html' => '<svg class="nc-driver-icon nc-whatsapp-icon" viewBox="0 0 32 32" aria-hidden="true">
                <path fill="currentColor" d="M16.04 3C9.39 3 4 8.39 4 15.04c0 2.12.55 4.18 1.6 6L4 29l8.16-1.56c1.74.95 3.7 1.45 5.88 1.45 6.65 0 12.04-5.39 12.04-12.04S22.69 3 16.04 3zm0 22.8c-1.88 0-3.6-.5-5.1-1.36l-.36-.21-4.22.8.82-4.1-.24-.38a9.64 9.64 0 0 1-1.5-5.14c0-5.3 4.32-9.62 9.62-9.62s9.62 4.32 9.62 9.62-4.32 9.62-9.62 9.62zm5.28-7.2c-.29-.14-1.7-.84-1.96-.94-.26-.1-.45-.14-.64.14-.19.29-.74.94-.91 1.13-.17.19-.34.22-.62.07-.29-.14-1.21-.45-2.3-1.42-.85-.76-1.42-1.69-1.59-1.98-.17-.29-.02-.45.13-.59.13-.13.29-.34.43-.5.14-.17.19-.29.29-.48.1-.19.05-.36-.02-.5-.07-.14-.64-1.55-.88-2.12-.23-.55-.47-.48-.64-.49h-.55c-.19 0-.5.07-.76.36-.26.29-1 1-1 2.43s1.03 2.81 1.18 3c.14.19 2.03 3.1 4.92 4.35.69.3 1.22.48 1.64.61.69.22 1.31.19 1.81.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.19-.55-.33z"/>
            </svg>',

            'sender' => Modules\PoliwangiNotification\Services\Notifications\Senders\WhatsAppSender::class,

            'rules' => [
                'api_url'      => 'required|string|max:255',
                'api_token'    => 'required|string|max:255',
                'phone_number' => 'required|string|max:30',
            ],

            'fields' => [
                'api_url' => [
                    'label'       => 'API URL',
                    'type'        => 'text',
                    'required'    => true,
                    'maxlength'   => 255,
                    'placeholder' => 'https://api.fonnte.com/send',
                    'default'     => 'https://api.fonnte.com/send',
                ],

                'api_token' => [
                    'label'       => 'API Token',
                    'type'        => 'password',
                    'required'    => true,
                    'secure'      => true,
                    'maxlength'   => 255,
                    'placeholder' => 'Token Fonnte',
                ],

                'phone_number' => [
                    'label'       => 'Nomor WhatsApp Tujuan',
                    'type'        => 'text',
                    'required'    => true,
                    'maxlength'   => 30,
                    'placeholder' => '6281234567890',
                ],
            ],

            'defaults' => [
                'target_field'  => 'target',
                'message_field' => 'message',
                'auth_type'     => 'token',
                'content_type'  => 'form',
                'country_code'  => '62',
            ],
        ],

    ],

    /*
     * Harus di luar drivers.
     * Ini bukan pilihan channel di form.
     */
    'webhooks' => [
        'telegram' =>
        Modules\PoliwangiNotification\Services\Notifications\Webhooks\TelegramWebhookHandler::class,
    ],

];
