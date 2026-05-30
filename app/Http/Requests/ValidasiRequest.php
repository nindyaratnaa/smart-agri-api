<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isBhabinkamtibmas();
    }

    public function rules(): array
    {
        return [
            'keputusan' => 'required|in:disetujui,ditolak',
            'catatan'   => [
                'nullable',
                'string',
                // Wajib dan minimal 20 karakter jika ditolak
                $this->keputusan === 'ditolak' ? 'required|min:20' : 'sometimes',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'keputusan.required' => 'Keputusan validasi wajib diisi.',
            'keputusan.in'       => 'Keputusan harus "disetujui" atau "ditolak".',
            'catatan.required'   => 'Catatan alasan wajib diisi jika data ditolak.',
            'catatan.min'        => 'Catatan alasan minimal 20 karakter.',
        ];
    }
}
