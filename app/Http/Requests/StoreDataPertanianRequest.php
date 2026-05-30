<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDataPertanianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAparaturDesa();
    }

    public function rules(): array
    {
        return [
            'wilayah_id'         => 'required|uuid|exists:wilayah,id',
            'komoditas'          => 'required|string|max:100',
            'varietas'           => 'nullable|string|max:100',
            'luas_lahan_ha'      => 'required|numeric|min:0.01|max:99999.99',
            'estimasi_panen_ton' => 'required|numeric|min:0.01|max:999999.99',
            'musim_tanam'        => 'required|in:MH,MK,MT',
            'periode'            => 'required|date_format:Y-m',
            'koordinat_lat'      => 'nullable|numeric|between:-90,90',
            'koordinat_lng'      => 'nullable|numeric|between:-180,180',
            'catatan_input'      => 'nullable|string|max:1000',
            'is_draft'           => 'sometimes|boolean',
            'foto'               => 'nullable|image|mimes:jpg,jpeg,png|max:5120', // 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'wilayah_id.required'         => 'Wilayah desa wajib dipilih.',
            'komoditas.required'          => 'Jenis komoditas wajib diisi.',
            'luas_lahan_ha.required'      => 'Luas lahan wajib diisi.',
            'luas_lahan_ha.min'           => 'Luas lahan minimal 0.01 ha.',
            'estimasi_panen_ton.required' => 'Estimasi panen wajib diisi.',
            'musim_tanam.required'        => 'Musim tanam wajib dipilih.',
            'musim_tanam.in'              => 'Musim tanam harus MH, MK, atau MT.',
            'periode.required'            => 'Periode pelaporan wajib diisi.',
            'periode.date_format'         => 'Format periode harus YYYY-MM (contoh: 2024-01).',
            'foto.max'                    => 'Ukuran foto maksimal 5MB.',
            'foto.mimes'                  => 'Format foto harus JPG atau PNG.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Normalize periode to first day of month for storage
        if ($this->filled('periode')) {
            $this->merge(['periode' => $this->periode . '-01']);
        }
    }
}
