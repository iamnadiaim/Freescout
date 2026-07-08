<?php

namespace Modules\LaporPoliwangi\Models;

use App\Mailbox;
use Illuminate\Database\Eloquent\Model;

class EndUserPortalSetting extends Model
{
    protected $table = 'end_user_portal_settings';

    protected $fillable = [
        'mailbox_id',
        'portal_url',
        'submit_ticket_title',
        'custom_fields',
        'subject_field',
        'consent_checkbox',
        'show_ticket_numbers',
        'footer',
        'only_existing_customers',

    ];

    protected $casts = [
        'subject_field' => 'boolean',
        'consent_checkbox' => 'boolean',
        'show_ticket_numbers' => 'boolean',
        'only_existing_customers' => 'boolean',
        'custom_fields' => 'array',

    ];

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class, 'mailbox_id');
    }
}
