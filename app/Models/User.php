<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'jenis_kelamin', 'email', 'password', 'email_verified_at',
        'dob', 'birth_hour', 'province', 'kota', 'kecamatan', 'kelurahan',
        'agama', 'agama_lainnya', 'anak_ke', 'jumlah_saudara', 'status_pernikahan',
        'pekerjaan', 'subscription_status', 'referred_by_affiliate_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'dob'               => 'date',
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    public function birthProfile()
    {
        return $this->hasOne(BirthProfile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function aiReports()
    {
        return $this->hasMany(AiReport::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class, 'referred_by_affiliate_id');
    }

    public function isSubscriber(): bool
    {
        if ($this->subscription_status !== 'active') return false;

        return $this->subscriptions()
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function syncSubscriptionStatus(): void
    {
        $aktif = $this->subscriptions()->where('ends_at', '>', now())->exists();
        if (!$aktif && $this->subscription_status === 'active') {
            $this->update(['subscription_status' => 'expired']);
        }
    }

    /**
     * Akses ke fitur: gratis (freemium) → selalu; pelanggan aktif → ya; selain itu harus punya
     * JATAH pembuatan dari pembelian satuan (config payment.single_generations per pembelian).
     */
    public function hasAccessToFeature(int $featureId): bool
    {
        // Fitur freemium (mis. Tafsir Mimpi) terbuka untuk semua pengguna tanpa bayar.
        $fitur = \App\Data\FiturData::cari($featureId);
        if ($fitur && !empty($fitur['freemium'])) return true;

        if ($this->isSubscriber()) return true;

        return $this->kuotaSisa($featureId) > 0;
    }

    /** Total jatah dari pembelian satuan yang lunas (tiap pembelian = N pembuatan). */
    public function kuotaTotal(int $featureId): int
    {
        $pembelian = $this->orders()->where('feature_id', $featureId)->where('status', 'settlement')->count();

        return $pembelian * (int) config('payment.single_generations', 3);
    }

    /** Jatah terpakai: pembuatan selesai, atau yang sedang diproses (15 menit terakhir — kegagalan tak menghanguskan jatah). */
    public function kuotaTerpakai(int $featureId): int
    {
        return AiReport::where('user_id', $this->id)
            ->where('feature_id', $featureId)
            ->where('consumed_quota', true)
            ->where(fn ($q) => $q->whereNotNull('response_text')->orWhere('cached_at', '>=', now()->subMinutes(15)))
            ->count();
    }

    public function kuotaSisa(int $featureId): int
    {
        return max(0, $this->kuotaTotal($featureId) - $this->kuotaTerpakai($featureId));
    }

    /** Pernah membeli fitur ini (untuk membedakan "belum beli" dari "jatah habis"). */
    public function pernahBeli(int $featureId): bool
    {
        return $this->orders()->where('feature_id', $featureId)->where('status', 'settlement')->exists();
    }

    /**
     * Nilai agama final untuk ditampilkan/dipakai AI — resolve "Others" ke teks manual.
     */
    public function getAgamaDisplayAttribute(): ?string
    {
        if ($this->agama === 'Others' && $this->agama_lainnya) {
            return $this->agama_lainnya;
        }

        return $this->agama;
    }
}
