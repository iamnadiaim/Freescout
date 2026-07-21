<?php

namespace Modules\PoliwangiPortal\Hooks;

class TicketNumberHook
{
    public static function register()
    {
        \Eventy::addFilter('email.reply_to_customer.subject', function ($subject, $conversation, $lastThread) {
            if (!$conversation || empty($conversation->number)) {
                return $subject;
            }

            $prefix = env('TICKETNUMBER_PREFIX', '[#');
            $suffix = env('TICKETNUMBER_SUFFIX', '] ');
            $ticketNumber = $prefix . $conversation->number . $suffix;

            $replyPrefix = '';

            if (preg_match('/^(Re:\s*)+/i', $subject, $matches)) {
                $replyPrefix = $matches[0];
                $subject = preg_replace('/^(Re:\s*)+/i', '', $subject);
            }

            if (strpos($subject, $ticketNumber) === 0) {
                return $replyPrefix . $subject;
            }

            return $replyPrefix . $ticketNumber . $subject;
        }, 20, 3);
    }
}
