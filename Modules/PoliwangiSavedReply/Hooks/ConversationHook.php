<?php

namespace Modules\PoliwangiSavedReply\Hooks;

use App\Mailbox;
use Eventy;
use Modules\PoliwangiSavedReply\Models\SavedReply;

class ConversationHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        self::registerSavedRepliesToolbar();
        self::registerSavedRepliesModal();
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

    //mengambil kategori saved reply
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

    //menampilkan saved replies toolbar pada halaman detail conversation
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

            echo view('poliwangisavedreply::conversation.saved_replies_toolbar', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'saved_reply_categories' => $saved_reply_categories,
            ])->render();
        }, 20);
    }

    //menampilkan saved replies modal pada halaman detail
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

            echo view('poliwangisavedreply::conversation.saved_replies_modal', [
                'conversation' => $conversation,
                'mailbox' => $mailbox,
                'saved_reply_categories' => $saved_reply_categories,
            ])->render();
        }, 20);
    }
}
