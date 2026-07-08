<?php

namespace Modules\LaporPoliwangi\Services\Notifications;


use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\LaporPoliwangi\Models\NotificationChannel;

class NotificationService
{
    /**
     * @var NotificationSenderFactory
     */
    private $factory;

    public function __construct(NotificationSenderFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Mengirim pesan melalui satu notification channel.
     *
     * @param NotificationChannel $channel
     * @param string|null         $recipient
     * @param string              $message
     * @param array               $options
     *
     * @return array
     *
     * @throws Exception
     */
    public function send(
        NotificationChannel $channel,
        $recipient,
        $message,
        array $options = []
    ) {
        if (!$channel->is_active) {
            throw new Exception(
                'Notification channel sedang tidak aktif.'
            );
        }

        try {
            $sender = $this->factory->make(
                $channel->type,
                is_array($channel->config)
                    ? $channel->config
                    : []
            );

            return $sender->send(
                $recipient,
                $message,
                $options
            );
        } catch (\Exception $e) {
            Log::error('Notification channel failed.', [
                'channel_id'   => $channel->id,
                'channel_name' => $channel->name,
                'channel_type' => $channel->type,
                'error'        => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Mengirim pesan manual melalui seluruh channel aktif
     * pada mailbox tanpa membaca notification rule.
     *
     * @param int    $mailboxId
     * @param string $message
     * @param array  $recipients
     * @param array  $options
     *
     * @return array
     */
    public function sendToMailbox(
        $mailboxId,
        $message,
        array $recipients = [],
        array $options = []
    ) {
        /** @var Collection|NotificationChannel[] $channels */
        $channels = NotificationChannel::active()
            ->forMailbox($mailboxId)
            ->get();

        $results = [];

        foreach ($channels as $channel) {
            /** @var NotificationChannel $channel */

            $recipient = array_key_exists(
                $channel->type,
                $recipients
            )
                ? $recipients[$channel->type]
                : null;

            try {
                $result = $this->send(
                    $channel,
                    $recipient,
                    $message,
                    $options
                );

                $results[] = [
                    'channel_id'   => $channel->id,
                    'channel_type' => $channel->type,
                    'success'      => true,
                    'result'       => $result,
                ];
            } catch (\Exception $e) {
                Log::error('Manual notification failed.', [
                    'mailbox_id'   => $mailboxId,
                    'channel_id'   => $channel->id,
                    'channel_type' => $channel->type,
                    'error'        => $e->getMessage(),
                ]);

                $results[] = [
                    'channel_id'   => $channel->id,
                    'channel_type' => $channel->type,
                    'success'      => false,
                    'error'        => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
