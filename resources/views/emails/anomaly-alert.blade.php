@extends('emails.layout')
@section('content')
<p>Halo <strong>{{ $recipient->name }}</strong>,</p>
<p>⚠️ Sistem mendeteksi <strong>anomali data</strong> yang signifikan:</p>
<div class="info-box" style="background:#fffbeb; border-color:#fde68a;">
    <div class="info-row"><span class="label">ID Data</span><span class="value">{{ $dataPertanian->id }}</span></div>
    <div class="info-row"><span class="label">Komoditas</span><span class="value">{{ $dataPertanian->komoditas }}</span></div>
    <div class="info-row"><span class="label">Wilayah</span><span class="value">{{ $dataPertanian->wilayah?->nama }}</span></div>
    <div class="info-row"><span class="label">Estimasi Panen</span><span class="value">{{ number_format($dataPertanian->estimasi_panen_ton, 2) }} Ton</span></div>
    <div class="info-row"><span class="label">Deviasi</span><span class="value" style="color:#d97706;">> 30% dari rata-rata historis</span></div>
</div>
<p>Harap tinjau data ini sebelum disetujui.</p>
<a class="btn" href="{{ env('FRONTEND_URL') }}/dashboard" style="background:#d97706;">Tinjau di Dashboard →</a>
@endsection
