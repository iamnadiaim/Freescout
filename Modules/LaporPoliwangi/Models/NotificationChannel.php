<?php

namespace Modules\LaporPoliwangi\Models;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class NotificationChannel extends Model
{
    protected $table = 'notification_channels';

    protected $fillable = [
        'mailbox_id',
        'name',
        'type',
        'config',
        'is_active',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function mailbox()
    {
        return $this->belongsTo(
            Mailbox::class,
            'mailbox_id',
            'id'
        );
    }


    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForMailbox($query, $mailboxId)
    {
        return $query->where(function ($query) use ($mailboxId) {
            $query->where('mailbox_id', $mailboxId)
                ->orWhereNull('mailbox_id');
        });
    }
}
