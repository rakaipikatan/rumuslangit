Selamat, {{ $affiliate->name }}!

Pendaftaran Anda sebagai afiliator Rumus Langit telah disetujui.

Link referral Anda:
{{ url('/') }}?ref={{ $affiliate->referral_code }}

Kode referral : {{ $affiliate->referral_code }}
Komisi        : {{ $affiliate->komisi_persen }}% dari setiap pembayaran yang berhasil dari pengguna yang mendaftar lewat link Anda.

Komisi dibayarkan ke rekening yang Anda daftarkan ({{ $affiliate->bank_nama }}) secara berkala setiap tanggal 25.

{{ strtoupper(__('afiliasi.syarat_title', [], 'id')) }}
@foreach(range(1, 9) as $n)
{{ $n }}. {{ __('afiliasi.syarat_' . $n, ['komisi' => $affiliate->komisi_persen, 'bulan' => config('payment.affiliate_commission_months', 12), 'hari' => config('payment.affiliate_hold_days', 14)], 'id') }}
@endforeach

Tidak menemukan email dari kami? Cek folder Spam atau Junk. Jika email ini masuk ke Spam, tandai sebagai "Bukan spam" agar email berikutnya masuk ke Inbox.

Rumus Langit - rumuslangit.biz.id
