<?php

namespace Modules\LaporPoliwangi\Hooks;

use App\Mailbox;
use Eventy;
use Modules\LaporPoliwangi\Models\CustomField;
use Modules\LaporPoliwangi\Models\CustomFieldValue;
use Modules\LaporPoliwangi\Models\SavedReply;
use Modules\LaporPoliwangi\Models\SatisfactionRating;

class ConversationHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        /*
         * Disisipkan di:
         * @action('conversation.after_subject_block', $conversation, $mailbox)
         *
         * Isi:
         * - conversation.satisfaction_rating_data
         * - conversation.custom_fields
         * - conversation.time_tracking
         */
        self::registerAfterSubjectBlock();

        /*
         * Disisipkan di:
         * @action('reply_form.after', $conversation)
         *
         * Isi:
         * - conversation.saved_replies_toolbar
         */
        self::registerSavedRepliesToolbar();

        /*
         * Disisipkan di:
         * @action('conversation.after_threads', $conversation)
         *
         * Isi:
         * - conversation.saved_replies_modal
         */
        self::registerSavedRepliesModal();

        /*
         * Disisipkan di:
         * @action('thread.after_person_action', ...)
         *
         * Isi:
         * - satisfaction rating badge ditampilkan di nama usernya
         */
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

    private static function getSavedReplyCategories($mailbox)
    {
        return SavedReply::with([
            'children' => function ($q) use ($mailbox) {
                $q->where(function ($query) use ($mailbox) {
                    $query->where('mailbox_id', $mailbox->id)
                        ->orWhere('is_global', 1);
                })->orderBy('name', 'asc');
            },
        ])
            ->whereNull('parent_id')
            ->where(function ($q) use ($mailbox) {
                $q->where('mailbox_id', $mailbox->id)
                    ->orWhere('is_global', 1);
            })
            ->orderBy('name', 'asc')
            ->get();
    }

    private static function getSatisfactionRatings($conversation)
    {
        return SatisfactionRating::where('conversation_id', $conversation->id)
            ->get()
            ->keyBy('thread_id');
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
            echo view('laporpoliwangi::conversation.satisfaction_rating_data', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
            ])->render();

            /*
             * Custom Fields.
             */
            $custom_fields = CustomField::where('mailbox_id', $mailbox->id)
                ->orderBy('id', 'asc')
                ->get();

            $custom_field_values = CustomFieldValue::where('conversation_id', $conversation->id)
                ->pluck('value', 'custom_field_id');
            /*
             * Time Tracking.
             */
            echo view('laporpoliwangi::conversation.time_tracking', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
            ])->render();

            echo view('laporpoliwangi::conversation.custom_fields', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'custom_fields' => $custom_fields,
                'custom_field_values' => $custom_field_values,
            ])->render();
        }, 20);
    }

    private static function registerSavedRepliesToolbar()
    {
        \Eventy::addAction('reply_form.after', function ($conversation) {
            if (!$conversation) {
                return;
            }

            $mailbox = self::resolveMailbox($conversation);

            if (!$mailbox) {
                return;
            }

            $saved_reply_categories = self::getSavedReplyCategories($mailbox);

            echo view('laporpoliwangi::conversation.saved_replies_toolbar', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'saved_reply_categories' => $saved_reply_categories,
            ])->render();
        }, 20);
    }

    private static function registerSavedRepliesModal()
    {
        \Eventy::addAction('conversation.after_threads', function ($conversation, $mailbox = null) {
            if (!$conversation) {
                return;
            }

            $mailbox = self::resolveMailbox($conversation, $mailbox);

            if (!$mailbox) {
                return;
            }

            $saved_reply_categories = self::getSavedReplyCategories($mailbox);

            echo view('laporpoliwangi::conversation.saved_replies_modal', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'saved_reply_categories' => $saved_reply_categories,
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

            $rating = \Modules\LaporPoliwangi\Models\SatisfactionRating::where('thread_id', $thread->id)->first();

            if ($rating) {
                echo view('laporpoliwangi::conversation.satisfaction_rating_badges', [
                    'rating' => $rating,
                ])->render();
            }
        }, 30, 5);
    }
}
