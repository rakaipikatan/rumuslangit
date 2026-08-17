<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahKecamatan extends Model
{
    public $timestamps = false;
    protected $table   = 'wilayah_kecamatan';
    protected $fillable = ['kode', 'kota_id', 'nama'];

    public function kota()
    {
        return $this->belongsTo(WilayahKota::class, 'kota_id');
    }

    public function kelurahan()
    {
        return $this->hasMany(WilayahKelurahan::class, 'kecamatan_id');
    }
}
