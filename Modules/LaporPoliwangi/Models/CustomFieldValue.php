<?php

namespace Modules\LaporPoliwangi\Models;

use App\Conversation;
use Illuminate\Database\Eloquent\Model;

class CustomFieldValue extends Model
{
    protected $table = 'custom_field_values';

    protected $fillable = [
        'custom_field_id',
        'conversation_id',
        'value'
    ];

    /**
     * Relasi ke CustomField
     */
    public function customField()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id', 'id');
    }

    /**
     * Relasi ke Conversation
     */
    public function conversation()
    {
        return $this->belongsTo(Conversation::class, 'conversation_id', 'id');
    }
}
