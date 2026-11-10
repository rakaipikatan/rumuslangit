<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Bukti Pembayaran</title></head>
<body style="margin:0;padding:24px;background:#f4f4f7;font-family:Arial,sans-serif;color:#222;">
  <div style="max-width:520px;margin:0 auto;background:#ffffff;border-radius:12px;padding:32px;">
    <p style="margin:0 0 4px;font-size:13px;color:#7c3aed;font-weight:bold;letter-spacing:1px;">✦ RUMUS LANGIT</p>
    <h2 style="margin:0 0 6px;">Pembayaran Berhasil</h2>
    <p style="margin:0 0 20px;color:#666;font-size:14px;">Halo {{ $nama }}, terima kasih. Berikut bukti pembayaran Anda.</p>

    <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;border-collapse:collapse;">
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">No. Order</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;font-family:monospace;">{{ $order->gateway_order_id }}</td></tr>
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">Produk</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;"><strong>{{ $produk }}</strong></td></tr>
      @if($langganan && $aktifSampai)
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">Aktif sampai</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;">{{ $aktifSampai->copy()->timezone('Asia/Jakarta')->format('d M Y') }}</td></tr>
      @endif
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">Metode</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;">{{ $metode }}</td></tr>
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">Waktu bayar</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;">{{ $waktuBayar }}</td></tr>
      <tr><td style="padding:9px 0;color:#777;border-bottom:1px solid #eee;">Akun</td><td style="padding:9px 0;text-align:right;border-bottom:1px solid #eee;">{{ $email }}</td></tr>
      <tr><td style="padding:12px 0;font-size:15px;"><strong>Total dibayar</strong></td><td style="padding:12px 0;text-align:right;font-size:18px;"><strong>Rp {{ number_format($order->amount, 0, ',', '.') }}</strong></td></tr>
    </table>

    <p style="text-align:center;margin:24px 0 8px;">
      <a href="{{ $link }}" style="display:inline-block;background:#7c3aed;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:bold;font-size:14px;">
        {{ $langganan ? 'Buka Katalog Analisis' : 'Mulai Isi Kuesioner' }}
      </a>
    </p>
    <p style="text-align:center;font-size:12px;color:#888;margin:0 0 20px;">Jika diminta masuk, gunakan <a href="{{ $loginUrl }}" style="color:#7c3aed;">kode yang dikirim ke email</a> ini.</p>

    <p style="font-size:12px;color:#999;line-height:1.5;border-top:1px solid #eee;padding-top:14px;margin:0;">
      Ini adalah bukti pembayaran elektronik dan bukan faktur pajak. Simpan email ini sebagai arsip.
      Ada kendala? Balas email ini dengan menyertakan nomor order di atas.
    </p>
  </div>
</body>
</html>
