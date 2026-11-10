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
    <p style="line-height:1.6;color:#666;font-size:13px;">Bagikan link di atas ke audiens Anda. Pengguna yang masuk lewat link Anda tercatat atas nama Anda secara permanen.</p>
  </div>
</body>
</html>
