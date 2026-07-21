<?php

namespace Modules\PoliwangiSatisfaction\Hooks;

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
        self::registerSatisfactionRatingBadges();
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

            /*
             * Satisfaction Rating Data.
             * Ini untuk menyiapkan data JS rating email.
             */
            echo view('poliwangisatisfaction::conversation.satisfaction_rating_data', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
            ])->render();
        }, 20);
    }

    private static function registerSatisfactionRatingBadges()
    {
        \Eventy::addAction('thread.after_person_action', function ($thread, $loop, $threads, $conversation, $mailbox) {
            if (!$conversation) {
                return;
            }

            $mailbox = self::resolveMailbox($conversation, $mailbox);

            if (!$mailbox) {
                return;
            }

            $rating = \Modules\PoliwangiSatisfaction\Models\SatisfactionRating::where('thread_id', $thread->id)->first();

            if ($rating) {
                echo view('poliwangisatisfaction::conversation.satisfaction_rating_badges', [
                    'rating' => $rating,
                ])->render();
            }
        }, 30, 5);
    }
}
