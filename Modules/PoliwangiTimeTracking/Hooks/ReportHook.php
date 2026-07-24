<?php

namespace Modules\PoliwangiTimeTracking\Hooks;

use Modules\PoliwangiTimeTracking\Models\TimeTrackingLog;

class ReportHook
{
    public static function register()
    {
        if (!class_exists('\Eventy')) {
            return;
        }

        \Eventy::addFilter('report.time_tracking.get_logs', function ($emptyCollection, $conversationIds, $startDateTime, $endDateTime) {
            if (empty($conversationIds)) {
                return collect();
            }

            return TimeTrackingLog::whereIn('conversation_id', $conversationIds)
                ->whereBetween('created_at', [$startDateTime, $endDateTime])
                ->where('seconds', '>', 0)
                ->get();
        }, 20, 4);
    }
}
