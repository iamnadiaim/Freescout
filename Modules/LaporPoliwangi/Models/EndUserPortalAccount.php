<?php

namespace Modules\LaporPoliwangi\Models;

use Illuminate\Database\Eloquent\Model;

class EndUserPortalAccount extends Model
{
    protected $table = 'end_user_portal_accounts';

    protected $fillable = [
        'customer_id',
        'email_id',
        'auth_type',
        'password',
        'sso_provider',
        'sso_id',
        'verification_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    public function isPasswordAccount()
    {
        return $this->auth_type === 'password';
    }

    public function isSsoAccount()
    {
        return $this->auth_type === 'sso';
    }
}
