<?php

namespace Modules\LaporPoliwangi\Models;

use App\Conversation;
use App\Customer;
use App\Mailbox;
use App\Thread;
use Illuminate\Database\Eloquent\Model;

class SatisfactionRating extends Model
{
    protected $table = 'satisfaction_ratings';

    const RATING_GREAT = 'great';
    const RATING_OKAY = 'okay';
    const RATING_NOT_GOOD = 'not_good';

    protected $fillable = [
        'mailbox_id',
        'conversation_id',
        'thread_id',
        'customer_id',
        'email',
        'rating',
        'comment',
    ];

    /**
     * Relasi ke mailbox.
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }

    /**
     * Relasi ke conversation / ticket.
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }

    /**
     * Relasi ke thread / pesan yang diberi rating.
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class, 'thread_id');
    }

    /**
     * Relasi ke customer / pelapor.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Label rating agar mudah ditampilkan.
     */
    public function getRatingLabelAttribute()
    {
        if ($this->rating === self::RATING_GREAT) {
            return 'Great';
        }

        if ($this->rating === self::RATING_OKAY) {
            return 'Okay';
        }

        if ($this->rating === self::RATING_NOT_GOOD) {
            return 'Not Good';
        }

        return '-';
    }

    /**
     * Emoji rating agar tampilan lebih jelas.
     */
    public function getRatingEmojiAttribute()
    {
        if ($this->rating === self::RATING_GREAT) {
            return '😊';
        }

        if ($this->rating === self::RATING_OKAY) {
            return '😐';
        }

        if ($this->rating === self::RATING_NOT_GOOD) {
            return '☹️';
        }

        return '';
    }

    /**
     * Warna badge rating.
     */
    public function getRatingColorAttribute()
    {
        if ($this->rating === self::RATING_GREAT) {
            return 'success';
        }

        if ($this->rating === self::RATING_OKAY) {
            return 'warning';
        }

        if ($this->rating === self::RATING_NOT_GOOD) {
            return 'danger';
        }

        return 'default';
    }
}
