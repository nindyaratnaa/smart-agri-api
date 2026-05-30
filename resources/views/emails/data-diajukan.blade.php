@extends('emails.layout')
@section('content')
<p>Halo <strong>{{ $validator->name }}</strong>,</p>
<p>Ada data pertanian baru yang menunggu verifikasi Anda:</p>
<div class="info-box">
    <div class="info-row"><span class="label">Komoditas</span><span class="value">{{ $dataPertanian->komoditas }}</span></div>
    <div class="info-row"><span class="label">Wilayah</span><span class="value">{{ $dataPertanian->wilayah?->nama }}</span></div>
    <div class="info-row"><span class="label">Luas Lahan</span><span class="value">{{ number_format($dataPertanian->luas_lahan_ha, 2) }} Ha</span></div>
    <div class="info-row"><span class="label">Estimasi Panen</span><span class="value">{{ number_format($dataPertanian->estimasi_panen_ton, 2) }} Ton</span></div>
    <div class="info-row"><span class="label">Diinput oleh</span><span class="value">{{ $dataPertanian->inputOleh?->name }}</span></div>
    <div class="info-row"><span class="label">Waktu Submit</span><span class="value">{{ $dataPertanian->submitted_at?->isoFormat('DD MMM YYYY HH:mm') }}</span></div>
</div>
<p>Harap verifikasi dalam <strong>48 jam</strong> untuk menjaga kelancaran sistem.</p>
<a class="btn" href="{{ env('FRONTEND_URL') }}/validasi/antrian">Buka Antrian Validasi →</a>
@endsection
