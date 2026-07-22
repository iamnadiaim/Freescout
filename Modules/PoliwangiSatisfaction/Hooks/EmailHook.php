<?php

namespace Modules\PoliwangiSatisfaction\Hooks;

use Eventy;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;

class EmailHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        // Hook before signature
        \Eventy::addAction('reply_email.before_signature', function ($thread, $loop, $threads, $conversation, $mailbox) {
            self::renderRatingBlock('above', $thread, $conversation, $mailbox, $loop);
        }, 20, 5);

        // Hook after signature
        \Eventy::addAction('reply_email.after_signature', function ($thread, $loop, $threads, $conversation, $mailbox) {
            self::renderRatingBlock('below', $thread, $conversation, $mailbox, $loop);
        }, 20, 5);
    }

    private static function renderRatingBlock($placement, $thread, $conversation, $mailbox, $loop)
    {
        // We only append to the FIRST thread (the one currently being sent/replied)
        if (!$loop->first) {
            return;
        }

        // We only append if the recipient is a Customer (source via WEB or EMAIL, not internal notes)
        if ($thread->type != \App\Thread::TYPE_MESSAGE) {
            return;
        }

        // Get the mailbox settings
        $setting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();

        if (!$setting || !$setting->enabled) {
            return;
        }

        // Check placement
        if ($setting->placement != $placement) {
            return;
        }

        // Check shortcode mode
        if ($setting->add_ratings_mode == 'shortcode') {
            if (strpos($thread->body, '{%ratings.add%}') === false) {
                return;
            }
        }

        // Generate a random token if not exists to secure the rating URL
        if (empty($conversation->satisfaction_rating_token)) {
            $conversation->satisfaction_rating_token = \Illuminate\Support\Str::random(40);
            $conversation->save();
        }

        // Render the blade template
        echo view('poliwangisatisfaction::emails.rating_block', [
            'thread' => $thread,
            'conversation' => $conversation,
            'mailbox' => $mailbox,
            'setting' => $setting
        ])->render();
    }
}
