<?php

namespace Modules\LaporPoliwangi\Models;

use App\Conversation;
use App\Mailbox;
use App\User;
use Illuminate\Database\Eloquent\Model;

class TimeTrackingLog extends Model
{
    protected $table = 'time_tracking_logs';

    protected $fillable = [
        'conversation_id',
        'mailbox_id',
        'user_id',
        'seconds',
        'source',
        'note',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'mailbox_id' => 'integer',
        'user_id' => 'integer',
        'seconds' => 'integer',
    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getMinutesAttribute()
    {
        return round($this->seconds / 60, 2);
    }

    public function getTimeSpentLabelAttribute()
    {
        if ($this->seconds <= 0) {
            return '-';
        }

        $hours = floor($this->seconds / 3600);
        $minutes = floor(($this->seconds % 3600) / 60);
        $seconds = $this->seconds % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }

        if ($minutes > 0) {
            $parts[] = $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes');
        }

        if ($hours == 0 && $minutes == 0 && $seconds > 0) {
            $parts[] = $seconds . ' seconds';
        }

        return implode(' ', $parts);
    }
}
