<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidasiRequest;
use App\Http\Resources\DataPertanianResource;
use App\Models\AuditLog;
use App\Models\DataPertanian;
use App\Models\Validasi;
use App\Mail\DataDisetujuiMail;
use App\Mail\DataDitolakMail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ValidasiController extends Controller
{
    /**
     * GET /api/validasi/antrian
     * Bhabinkamtibmas: list data menunggu validasi di wilayahnya
     */
    public function antrian(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = DataPertanian::with(['wilayah', 'inputOleh', 'validasi'])
            ->menunggu()
            ->submitted()
            ->orderBy('submitted_at');

        // Bhabinkamtibmas only sees their wilayah
        if ($user->isBhabinkamtibmas() && $user->wilayah_id) {
            $query->whereHas('wilayah', fn($q) =>
                $q->where('id', $user->wilayah_id)
                  ->orWhere('parent_id', $user->wilayah_id)
            );
        }

        // Flag items awaiting > 48 hours
        $data = $query->paginate($request->integer('per_page', 15));

        $items = $data->getCollection()->map(function ($item) {
            $hoursWaiting = $item->submitted_at
                ? now()->diffInHours($item->submitted_at)
                : 0;
            return array_merge(
                (new DataPertanianResource($item))->toArray(request()),
                [
                    'hours_waiting'  => $hoursWaiting,
                    'needs_reminder' => $hoursWaiting >= 48,
                    'needs_escalation' => $hoursWaiting >= 72,
                ]
            );
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    /**
     * POST /api/validasi/{dataPertanianId}
     * Bhabinkamtibmas: approve or reject
     */
    public function validasi(ValidasiRequest $request, string $dataPertanianId): JsonResponse
    {
        $user = $request->user();

        $dataPertanian = DataPertanian::with(['wilayah', 'inputOleh'])
            ->menunggu()
            ->submitted()
            ->findOrFail($dataPertanianId);

        // Ensure bhabinkamtibmas is assigned to this wilayah
        if ($user->isBhabinkamtibmas() && $user->wilayah_id) {
            $wilayahOk = $dataPertanian->wilayah_id === $user->wilayah_id
                || $dataPertanian->wilayah?->parent_id === $user->wilayah_id;

            if (!$wilayahOk) {
                return response()->json(['message' => 'Anda tidak berwenang memvalidasi data wilayah ini.'], 403);
            }
        }

        DB::transaction(function () use ($request, $user, $dataPertanian) {
            $keputusan = $request->keputusan;

            // Create validasi record
            Validasi::create([
                'data_pertanian_id' => $dataPertanian->id,
                'validator_id'      => $user->id,
                'keputusan'         => $keputusan,
                'catatan'           => $request->catatan,
                'validated_at'      => now(),
            ]);

            // Update data status
            $dataPertanian->update(['status_validasi' => $keputusan]);

            AuditLog::record(
                'validasi_' . $keputusan,
                'data_pertanian',
                $dataPertanian->id,
                ['status_validasi' => 'menunggu'],
                ['status_validasi' => $keputusan, 'catatan' => $request->catatan]
            );

            // Send notification email to aparatur desa
            $aparatur = $dataPertanian->inputOleh;
            if ($keputusan === 'disetujui') {
                Mail::to($aparatur->email)->queue(new DataDisetujuiMail($dataPertanian, $user));
            } else {
                Mail::to($aparatur->email)->queue(new DataDitolakMail($dataPertanian, $user, $request->catatan));
            }
        });

        return response()->json([
            'message' => $request->keputusan === 'disetujui'
                ? 'Data berhasil disetujui.'
                : 'Data berhasil ditolak. Notifikasi telah dikirim ke aparatur desa.',
            'data' => new DataPertanianResource($dataPertanian->fresh()->load(['wilayah', 'inputOleh', 'validasiTerakhir.validator'])),
        ]);
    }

    /**
     * GET /api/validasi/riwayat
     * History of validations done by current bhabinkamtibmas (or all for admin)
     */
    public function riwayat(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Validasi::with(['dataPertanian.wilayah', 'validator'])
            ->orderByDesc('validated_at');

        if ($user->isBhabinkamtibmas()) {
            $query->where('validator_id', $user->id);
        }

        if ($request->filled('keputusan')) {
            $query->where('keputusan', $request->keputusan);
        }

        $riwayat = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data' => $riwayat->map(fn($v) => [
                'id'           => $v->id,
                'keputusan'    => $v->keputusan,
                'catatan'      => $v->catatan,
                'validated_at' => $v->validated_at?->toIso8601String(),
                'validator'    => ['id' => $v->validator?->id, 'name' => $v->validator?->name],
                'data_pertanian' => $v->dataPertanian ? new DataPertanianResource($v->dataPertanian) : null,
            ]),
            'meta' => [
                'current_page' => $riwayat->currentPage(),
                'last_page'    => $riwayat->lastPage(),
                'total'        => $riwayat->total(),
            ],
        ]);
    }
}
