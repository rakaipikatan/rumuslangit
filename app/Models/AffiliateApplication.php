<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateApplication extends Model
{
    protected $fillable = [
        'name', 'email', 'phone', 'promosi',
        'bank_nama', 'bank_rekening', 'bank_atas_nama',
        'status', 'affiliate_id', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }
}
