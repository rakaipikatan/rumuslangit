<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /** Marker feature_id untuk order langganan (bukan pembelian fitur satuan). */
    const SUBSCRIPTION_MONTHLY_FEATURE_ID = -1;
    const SUBSCRIPTION_YEARLY_FEATURE_ID  = -2;

    protected $fillable = [
        'user_id', 'feature_id', 'package_id', 'amount',
        'unique_code', 'transfer_amount', 'bank_tujuan',
        'payment_method', 'gateway_order_id', 'status', 'settled_at',
    ];

    protected $casts = [
        'settled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isSettled(): bool
    {
        return $this->status === 'settlement';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
