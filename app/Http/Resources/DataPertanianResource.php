<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataPertanianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'komoditas'           => $this->komoditas,
            'varietas'            => $this->varietas,
            'luas_lahan_ha'       => (float) $this->luas_lahan_ha,
            'estimasi_panen_ton'  => (float) $this->estimasi_panen_ton,
            'musim_tanam'         => $this->musim_tanam,
            'status_validasi'     => $this->status_validasi,
            'periode'             => $this->periode?->format('Y-m'),
            'foto_url'            => $this->foto_url,
            'koordinat_lat'       => $this->koordinat_lat ? (float) $this->koordinat_lat : null,
            'koordinat_lng'       => $this->koordinat_lng ? (float) $this->koordinat_lng : null,
            'catatan_input'       => $this->catatan_input,
            'is_draft'            => $this->is_draft,
            'submitted_at'        => $this->submitted_at?->toIso8601String(),
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),

            // Relationships
            'wilayah' => $this->whenLoaded('wilayah', fn() => [
                'id'        => $this->wilayah->id,
                'kode'      => $this->wilayah->kode,
                'nama'      => $this->wilayah->nama,
                'full_path' => $this->wilayah->full_path,
            ]),
            'input_oleh' => $this->whenLoaded('inputOleh', fn() => [
                'id'      => $this->inputOleh->id,
                'name'    => $this->inputOleh->name,
                'jabatan' => $this->inputOleh->jabatan,
            ]),
            'validasi_terakhir' => $this->whenLoaded('validasiTerakhir', fn() => $this->validasiTerakhir ? [
                'keputusan'    => $this->validasiTerakhir->keputusan,
                'catatan'      => $this->validasiTerakhir->catatan,
                'validated_at' => $this->validasiTerakhir->validated_at?->toIso8601String(),
                'validator'    => [
                    'id'   => $this->validasiTerakhir->validator?->id,
                    'name' => $this->validasiTerakhir->validator?->name,
                ],
            ] : null),
        ];
    }
}
