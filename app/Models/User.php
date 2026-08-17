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
        'pekerjaan', 'subscription_status',
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

    public function hasAccessToFeature(int $featureId): bool
    {
        if ($this->isSubscriber()) return true;

        return $this->orders()
            ->where('feature_id', $featureId)
            ->where('status', 'settlement')
            ->exists();
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
