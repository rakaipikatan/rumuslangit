<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'email', 'jenis', 'status', 'pesan_error', 'biaya', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    /** Label ramah untuk tiap jenis email. */
    public const JENIS = [
        'otp'                => 'Kode verifikasi (OTP)',
        'login_kode'         => 'Kode login',
        'bukti_bayar'        => 'Bukti pembayaran',
        'hoki_harian'        => 'Hoki Harian',
        'afiliasi_disetujui' => 'Persetujuan afiliasi',
        'tes_admin'          => 'Tes (panel admin)',
        'tes_cli'            => 'Tes (perintah mail:uji)',
        'antrean'            => 'Antrean gagal (tanpa rincian)',
        'lainnya'            => 'Lainnya',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeTerkirim($q)
    {
        return $q->where('status', 'terkirim');
    }
}
