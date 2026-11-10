@php
    $p = ['komisi' => $komisi, 'bulan' => config('payment.affiliate_commission_months', 12), 'hari' => config('payment.affiliate_hold_days', 14)];
@endphp
<ol class="space-y-2.5 text-xs text-white/55 leading-relaxed list-decimal pl-5">
    @foreach(range(1, 9) as $n)
    <li>{{ __('afiliasi.syarat_' . $n, $p) }}</li>
    @endforeach
</ol>
