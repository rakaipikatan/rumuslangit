PEMBAYARAN BERHASIL - RUMUS LANGIT

Halo {{ $nama }}, terima kasih. Berikut bukti pembayaran Anda.

No. Order    : {{ $order->gateway_order_id }}
Produk       : {{ $produk }}
@if($langganan && $aktifSampai)
Aktif sampai : {{ $aktifSampai->copy()->timezone('Asia/Jakarta')->format('d M Y') }}
@endif
Metode       : {{ $metode }}
Waktu bayar  : {{ $waktuBayar }}
Akun         : {{ $email }}
TOTAL DIBAYAR: Rp {{ number_format($order->amount, 0, ',', '.') }}

{{ $langganan ? 'Buka katalog analisis' : 'Mulai isi kuesioner' }}:
{{ $link }}

Jika diminta masuk, gunakan kode yang dikirim ke email ini: {{ $loginUrl }}

Ini adalah bukti pembayaran elektronik dan bukan faktur pajak. Simpan email ini sebagai arsip.
Ada kendala? Balas email ini dengan menyertakan nomor order di atas.

Rumus Langit - rumuslangit.biz.id
