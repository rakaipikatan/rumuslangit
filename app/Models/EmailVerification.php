<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email_hash', 'attempts', 'verified_at', 'trial_used_at',
    ];

    protected $casts = [
        'verified_at'   => 'datetime',
        'trial_used_at' => 'datetime',
    ];
}
