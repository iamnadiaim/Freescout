<?php

namespace Modules\LaporPoliwangi\Models;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class CustomField extends Model
{
    protected $table = 'custom_fields';

    protected $fillable = [
        'nama_field',
        'type_field',
        'options',
        'show_in_conversation_list',
        'required',
        'mailbox_id',
    ];

    protected $casts = [
        'options' => 'array',
        'show_in_conversation_list' => 'boolean',
        'required' => 'boolean',
    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }

    public function values()
    {
        return $this->hasMany(CustomFieldValue::class, 'custom_field_id');
    }
}
