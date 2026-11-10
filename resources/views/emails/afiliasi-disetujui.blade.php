<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Afiliasi Rumus Langit</title></head>
<body style="margin:0;padding:24px;background:#f4f4f7;font-family:Arial,sans-serif;color:#222;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;">
    <h2 style="margin:0 0 12px;">Selamat, {{ $affiliate->name }}!</h2>
    <p style="line-height:1.6;">Pendaftaran Anda sebagai afiliator <strong>Rumus Langit</strong> telah disetujui.</p>
    <p style="line-height:1.6;margin-bottom:6px;">Link referral Anda:</p>
    <p style="background:#f0ebff;border-radius:8px;padding:12px;word-break:break-all;font-family:monospace;">
      {{ url('/') }}?ref={{ $affiliate->referral_code }}
    </p>
    <p style="line-height:1.6;">Kode referral: <strong>{{ $affiliate->referral_code }}</strong><br>
    Komisi: <strong>{{ $affiliate->komisi_persen }}%</strong> dari setiap pembayaran yang berhasil dari pengguna yang mendaftar lewat link Anda.</p>
    <p style="line-height:1.6;">Komisi dibayarkan ke rekening yang Anda daftarkan ({{ $affiliate->bank_nama }}), secara berkala setiap tanggal 25.</p>
    <h3 style="margin:24px 0 8px;font-size:15px;">{{ __('afiliasi.syarat_title', [], 'id') }}</h3>
    <ol style="line-height:1.55;font-size:13px;color:#444;padding-left:20px;margin:0 0 16px;">
      @foreach(range(1, 9) as $n)
      <li style="margin-bottom:6px;">{{ __('afiliasi.syarat_' . $n, ['komisi' => $affiliate->komisi_persen, 'bulan' => config('payment.affiliate_commission_months', 12), 'hari' => config('payment.affiliate_hold_days', 14)], 'id') }}</li>
      @endforeach
    </ol>
    <p style="line-height:1.5;color:#777;font-size:12px;">Tidak menemukan email dari kami? Cek folder Spam atau Junk. Jika email ini masuk ke Spam, tandai sebagai &quot;Bukan spam&quot; agar email berikutnya masuk ke Inbox.</p>
    <p style="line-height:1.6;color:#666;font-size:13px;">Bagikan link di atas ke audiens Anda. Pengguna yang masuk lewat link Anda tercatat atas nama Anda secara permanen.</p>
  </div>
</body>
</html>
