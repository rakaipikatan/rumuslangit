<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BirthProfile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'weton_neptune', 'shio',
        'zodiac_sign', 'ascendant', 'element', 'calculated_at',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
