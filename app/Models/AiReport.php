<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiReport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'feature_id', 'locale', 'consumed_quota', 'data_hash',
        'prompt_used', 'response_text', 'tokens_used', 'cached_at',
    ];

    protected $casts = [
        'cached_at'      => 'datetime',
        'consumed_quota' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
