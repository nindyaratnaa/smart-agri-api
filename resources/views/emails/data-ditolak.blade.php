@extends('emails.layout')
@section('content')
<p>Halo <strong>{{ $dataPertanian->inputOleh?->name }}</strong>,</p>
<p>❌ Data pertanian yang Anda ajukan <strong>ditolak</strong> oleh Bhabinkamtibmas dan perlu direvisi.</p>
<div class="info-box" style="background:#fef2f2; border-color:#fecaca;">
    <div class="info-row"><span class="label">Komoditas</span><span class="value">{{ $dataPertanian->komoditas }}</span></div>
    <div class="info-row"><span class="label">Ditolak oleh</span><span class="value">{{ $validator->name }}</span></div>
    <div class="info-row"><span class="label">Alasan Penolakan</span><span class="value" style="color:#dc2626;">{{ $alasan }}</span></div>
</div>
<p>Silakan revisi data Anda sesuai catatan di atas dan submit ulang.</p>
<a class="btn" href="{{ env('FRONTEND_URL') }}/data-pertanian" style="background:#dc2626;">Revisi Data →</a>
@endsection
