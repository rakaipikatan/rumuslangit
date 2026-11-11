@php
    $p = ['komisi' => $komisi, 'bulan' => config('payment.affiliate_commission_months', 12), 'hari' => config('payment.affiliate_hold_days', 14), 'minimum' => number_format((int) config('payment.affiliate_min_withdrawal', 100000), 0, ',', '.')];
@endphp
<ol class="space-y-2.5 text-xs text-white/55 leading-relaxed list-decimal pl-5">
    @foreach(range(1, 9) as $n)
    <li>{{ __('afiliasi.syarat_' . $n, $p) }}</li>
    @endforeach
</ol>
