<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahKota extends Model
{
    public $timestamps = false;
    protected $table   = 'wilayah_kota';
    protected $fillable = ['kode', 'provinsi_id', 'nama', 'tipe'];

    public function provinsi()
    {
        return $this->belongsTo(WilayahProvinsi::class, 'provinsi_id');
    }

    public function kecamatan()
    {
        return $this->hasMany(WilayahKecamatan::class, 'kota_id');
    }
}
