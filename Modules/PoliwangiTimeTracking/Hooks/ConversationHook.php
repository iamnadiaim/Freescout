<?php

namespace Modules\PoliwangiTimeTracking\Hooks;

use App\Mailbox;
use Eventy;

class ConversationHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        self::registerAfterSubjectBlock();
        self::registerStatusChangeEvent();
    }

    private static function resolveMailbox($conversation, $mailbox = null)
    {
        if ($mailbox) {
            return $mailbox;
        }

        if (!empty($conversation->mailbox)) {
            return $conversation->mailbox;
        }

        if (!empty($conversation->mailbox_id)) {
            return Mailbox::find($conversation->mailbox_id);
        }

        return null;
    }

    private static function registerAfterSubjectBlock()
    {
        \Eventy::addAction('conversation.after_subject_block', function ($conversation, $mailbox = null) {
            if (!$conversation) {
                return;
            }

            $mailbox = self::resolveMailbox($conversation, $mailbox);

            if (!$mailbox) {
                return;
            }

            echo view('poliwangitimetracking::conversation.time_tracking', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
            ])->render();
        }, 20);
    }

    private static function registerStatusChangeEvent()
    {
        \Illuminate\Support\Facades\Event::listen('App\Events\ConversationStatusChanged', function ($event) {
            $conversation = $event->conversation ?? null;
            if (!$conversation) {
                return;
            }

            $userId = auth()->id() ?? $conversation->user_id;

            if ($userId) {
                \Modules\PoliwangiTimeTracking\Services\TimeTrackingService::logStatusChange(
                    $conversation,
                    $userId,
                    null,
                    $conversation->status
                );
            }
        });
    }
}
