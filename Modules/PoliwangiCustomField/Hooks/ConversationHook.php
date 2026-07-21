<?php

namespace Modules\PoliwangiCustomField\Hooks;

use App\Mailbox;
use Eventy;
use Modules\PoliwangiCustomField\Models\CustomField;
use Modules\PoliwangiCustomField\Models\CustomFieldValue;

class ConversationHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        self::registerAfterSubjectBlock();
        self::registerCreateForm();
        self::registerCreateEvent();
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
             * Custom Fields.
             */
            $custom_fields = CustomField::where('mailbox_id', $mailbox->id)
                ->orderBy('id', 'asc')
                ->get();

            $custom_field_values = CustomFieldValue::where('conversation_id', $conversation->id)
                ->pluck('value', 'custom_field_id');

            echo view('poliwangicustomfield::conversation.custom_fields', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'custom_fields' => $custom_fields,
                'custom_field_values' => $custom_field_values,
            ])->render();
        }, 20);
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
                echo view('poliwangicustomfield::partials.conversation_custom_fields_create', [
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
}
