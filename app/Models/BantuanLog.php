<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BantuanLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'affiliate_id', 'pertanyaan', 'jawaban', 'sumber', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];
}
