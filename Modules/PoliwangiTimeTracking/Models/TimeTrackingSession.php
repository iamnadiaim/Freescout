<?php

namespace Modules\PoliwangiTimeTracking\Models;

use App\Conversation;
use App\Mailbox;
use App\User;
use Illuminate\Database\Eloquent\Model;

class TimeTrackingSession extends Model
{
    protected $table = 'time_tracking_sessions';

    protected $fillable = [
        'conversation_id',
        'mailbox_id',
        'user_id',
        'started_at',
        'elapsed_seconds',
        'status',
        'source',
        'thread_id',
    ];

    protected $dates = [
        'started_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'conversation_id' => 'integer',
        'mailbox_id' => 'integer',
        'user_id' => 'integer',
        'elapsed_seconds' => 'integer',
        'thread_id' => 'integer',
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

    public function getCurrentSecondsAttribute()
    {
        $seconds = (int) $this->elapsed_seconds;

        if ($this->status === 'running' && $this->started_at) {
            $seconds += now()->diffInSeconds($this->started_at);
        }

        return $seconds;
    }
}
