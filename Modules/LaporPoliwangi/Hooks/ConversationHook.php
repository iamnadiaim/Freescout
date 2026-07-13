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

        /*
         * Disisipkan di:
         * @action('conversation.create_form.after_subject', $conversation, $mailbox, $thread)
         *
         * Isi:
         * - Form custom fields saat create conversation
         */
        self::registerCreateForm();

        /*
         * Event Listener saat Conversation baru dibuat oleh User (Agent/Admin)
         */
        self::registerCreateEvent();

        /*
         * Event Listener saat Status berubah (misal: di-Closed)
         */
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

    private static function registerCreateForm()
    {
        \Eventy::addAction('conversation.create_form.after_subject', function ($conversation, $mailbox = null, $thread = null) {
            if (!$mailbox) {
                $mailbox_id = request()->route('mailbox_id') ?? request()->input('mailbox_id');
                if ($mailbox_id) {
                    $mailbox = Mailbox::find($mailbox_id);
                }
            }

            if (!$mailbox) {
                return;
            }

            $custom_fields = CustomField::where('mailbox_id', $mailbox->id)
                ->orderBy('id', 'asc')
                ->get();

            if ($custom_fields->count() > 0) {
                echo view('laporpoliwangi::partials.conversation_custom_fields_create', [
                    'custom_fields' => $custom_fields,
                ])->render();
            }
        }, 20, 3);
    }

    private static function registerCreateEvent()
    {
        \Illuminate\Support\Facades\Event::listen('App\Events\UserCreatedConversation', function ($event) {
            $conversation = $event->conversation ?? null;
            $request = request();

            if (!$conversation || !$request->has('custom_fields')) {
                return;
            }

            $fields = $request->input('custom_fields');
            if (is_array($fields)) {
                foreach ($fields as $field_id => $value) {
                    if (is_array($value)) {
                        $value = implode(',', $value);
                    }
                    
                    if (trim((string)$value) === '') {
                        continue;
                    }

                    CustomFieldValue::updateOrCreate(
                        [
                            'conversation_id' => $conversation->id,
                            'custom_field_id' => $field_id,
                        ],
                        [
                            'value' => $value,
                        ]
                    );
                }
            }
        });
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
                \Modules\LaporPoliwangi\Services\TimeTrackingService::logStatusChange(
                    $conversation,
                    $userId,
                    null,
                    $conversation->status
                );
            }
        });
    }
}
