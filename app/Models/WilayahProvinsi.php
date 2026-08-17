<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahProvinsi extends Model
{
    public $timestamps = false;
    protected $table   = 'wilayah_provinsi';
    protected $fillable = ['kode', 'nama'];

    public function kota()
    {
        return $this->hasMany(WilayahKota::class, 'provinsi_id');
    }
}
