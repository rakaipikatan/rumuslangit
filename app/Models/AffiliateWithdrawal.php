<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateWithdrawal extends Model
{
    protected $fillable = ['affiliate_id', 'jumlah', 'status', 'bank_nama', 'bank_rekening', 'bank_atas_nama', 'paid_at'];

    protected $casts = ['paid_at' => 'datetime'];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function commissions()
    {
        return $this->hasMany(AffiliateCommission::class, 'withdrawal_id');
    }
}
