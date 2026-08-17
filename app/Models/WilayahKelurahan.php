<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WilayahKelurahan extends Model
{
    public $timestamps = false;
    protected $table   = 'wilayah_kelurahan';
    protected $fillable = ['kode', 'kecamatan_id', 'nama'];

    public function kecamatan()
    {
        return $this->belongsTo(WilayahKecamatan::class, 'kecamatan_id');
    }
}
