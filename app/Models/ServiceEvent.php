<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['layanan', 'ok', 'pesan', 'created_at'];

    protected $casts = ['ok' => 'boolean', 'created_at' => 'datetime'];
}
