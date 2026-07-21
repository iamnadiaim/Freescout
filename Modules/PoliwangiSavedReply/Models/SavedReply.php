<?php

namespace Modules\PoliwangiSavedReply\Models;

use App\Mailbox;
use App\User;
use Illuminate\Database\Eloquent\Model;

class SavedReply extends Model
{
    protected $table = 'saved_replies';

    protected $fillable = [
        'mailbox_id',
        'parent_id',
        'name',
        'reply',
        'is_global',
        'user_id',
    ];

    protected $casts = [
        'is_global' => 'boolean',
    ];

    /**
     * Mailbox asal saved reply.
     */
    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id', 'id');
    }

    /**
     * Parent saved reply.
     * Parent ini berfungsi sebagai kategori.
     */
    public function parent()
    {
        return $this->belongsTo(SavedReply::class, 'parent_id', 'id');
    }

    /**
     * Child saved replies.
     * Ini isi balasan yang masuk ke dalam kategori.
     */
    public function children()
    {
        return $this->hasMany(SavedReply::class, 'parent_id', 'id')
            ->orderBy('name', 'asc');
    }


    /**
     * User pembuat saved reply.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Cek apakah item ini kategori.
     */
    public function isCategory()
    {
        return is_null($this->parent_id);
    }

    /**
     * Cek apakah item ini reply biasa.
     */
    public function isReply()
    {
        return !is_null($this->parent_id);
    }
}
