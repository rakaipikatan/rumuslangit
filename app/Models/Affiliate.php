<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'referral_code',
        'bank_nama', 'bank_rekening', 'bank_atas_nama',
        'komisi_persen', 'status',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'referred_by_affiliate_id');
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
