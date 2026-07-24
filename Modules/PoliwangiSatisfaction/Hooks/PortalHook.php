<?php

namespace Modules\PoliwangiSatisfaction\Hooks;

use Modules\PoliwangiSatisfaction\Models\SatisfactionRating;
use Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting;
use Illuminate\Support\Facades\Schema;

class PortalHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        \Eventy::addAction('portal.ticket.thread_footer', function ($thread, $mailbox, $conversation, $email) {
            $ratingSetting = null;
            $currentRating = null;

            if (class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRatingSetting')) {
                $settingModel = new SatisfactionRatingSetting();
                if (Schema::hasTable($settingModel->getTable())) {
                    $ratingSetting = SatisfactionRatingSetting::where('mailbox_id', $mailbox->id)->first();
                }
                
                if ($ratingSetting && class_exists('\Modules\PoliwangiSatisfaction\Models\SatisfactionRating')) {
                    $ratingModel = new SatisfactionRating();
                    if (Schema::hasTable($ratingModel->getTable())) {
                        $currentRating = SatisfactionRating::where('mailbox_id', $mailbox->id)
                            ->where('conversation_id', $conversation->id)
                            ->where('email', $email)
                            ->where('thread_id', $thread->id)
                            ->first();
                    }
                }
            }

            echo view('poliwangisatisfaction::end_user_portal.satisfaction_rating', [
                'thread' => $thread,
                'mailbox' => $mailbox,
                'conversation' => $conversation,
                'email' => $email,
                'ratingSetting' => $ratingSetting,
                'currentRating' => $currentRating
            ])->render();
        }, 20, 4);
    }
}
