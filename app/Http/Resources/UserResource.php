<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'nik'        => $this->nik,
            'jabatan'    => $this->jabatan,
            'no_hp'      => $this->no_hp,
            'is_active'  => $this->is_active,
            'wilayah'    => $this->whenLoaded('wilayah', fn() => [
                'id'    => $this->wilayah->id,
                'kode'  => $this->wilayah->kode,
                'nama'  => $this->wilayah->nama,
                'level' => $this->wilayah->level,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
