<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfDownload extends Model
{
    protected $fillable = [
        'user_id', 'report_id', 'file_path',
        'signed_url', 'url_expires_at', 'downloaded_at',
    ];

    protected $casts = [
        'url_expires_at' => 'datetime',
        'downloaded_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function report()
    {
        return $this->belongsTo(AiReport::class, 'report_id');
    }

    public function isExpired(): bool
    {
        return now()->isAfter($this->url_expires_at);
    }
}
