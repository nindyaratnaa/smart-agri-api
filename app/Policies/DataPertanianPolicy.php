<?php

namespace App\Policies;

use App\Models\DataPertanian;
use App\Models\User;

class DataPertanianPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can list (filtered by RBAC in controller)
    }

    public function view(User $user, DataPertanian $dataPertanian): bool
    {
        if ($user->isAparaturDesa()) {
            return $dataPertanian->input_oleh === $user->id;
        }

        if ($user->isBhabinkamtibmas()) {
            return $dataPertanian->wilayah_id === $user->wilayah_id
                || $dataPertanian->wilayah?->parent_id === $user->wilayah_id;
        }

        return $user->canViewNationalData();
    }

    public function create(User $user): bool
    {
        return $user->isAparaturDesa();
    }

    public function update(User $user, DataPertanian $dataPertanian): bool
    {
        return $user->isAparaturDesa()
            && $dataPertanian->input_oleh === $user->id
            && $dataPertanian->status_validasi !== 'disetujui';
    }

    public function delete(User $user, DataPertanian $dataPertanian): bool
    {
        return $user->isAparaturDesa()
            && $dataPertanian->input_oleh === $user->id
            && $dataPertanian->status_validasi !== 'disetujui';
    }

    public function validasi(User $user, DataPertanian $dataPertanian): bool
    {
        if (!$user->isBhabinkamtibmas()) return false;

        return $dataPertanian->status_validasi === 'menunggu'
            && !$dataPertanian->is_draft;
    }
}
