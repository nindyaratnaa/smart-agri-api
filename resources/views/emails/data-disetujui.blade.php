@extends('emails.layout')
@section('content')
<p>Halo <strong>{{ $dataPertanian->inputOleh?->name }}</strong>,</p>
<p>✅ Data pertanian yang Anda ajukan telah <strong>disetujui</strong> oleh Bhabinkamtibmas.</p>
<div class="info-box">
    <div class="info-row"><span class="label">Komoditas</span><span class="value">{{ $dataPertanian->komoditas }}</span></div>
    <div class="info-row"><span class="label">Luas Lahan</span><span class="value">{{ number_format($dataPertanian->luas_lahan_ha, 2) }} Ha</span></div>
    <div class="info-row"><span class="label">Estimasi Panen</span><span class="value">{{ number_format($dataPertanian->estimasi_panen_ton, 2) }} Ton</span></div>
    <div class="info-row"><span class="label">Divalidasi oleh</span><span class="value">{{ $validator->name }}</span></div>
    <div class="info-row"><span class="label">Waktu Validasi</span><span class="value">{{ now()->isoFormat('DD MMM YYYY HH:mm') }}</span></div>
</div>
<p>Data Anda kini masuk ke database terpusat dan dapat diakses oleh Dinas Pertanian.</p>
<a class="btn" href="{{ env('FRONTEND_URL') }}/dashboard">Lihat Dashboard →</a>
@endsection
