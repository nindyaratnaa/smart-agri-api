<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDataPertanianRequest;
use App\Http\Resources\DataPertanianResource;
use App\Jobs\SendAnomalyAlert;
use App\Models\AuditLog;
use App\Models\DataPertanian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DataPertanianController extends Controller
{
    /**
     * GET /api/data-pertanian
     * Aparatur Desa: hanya data miliknya
     * Bhabinkamtibmas: semua data yang menunggu di wilayahnya
     * Dinas/Pusat/Admin: semua data dengan filter
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = DataPertanian::with(['wilayah', 'inputOleh', 'validasiTerakhir.validator'])
            ->submitted()
            ->orderByDesc('created_at');

        // RBAC filtering
        if ($user->isAparaturDesa()) {
            $query->where('input_oleh', $user->id);
        } elseif ($user->isBhabinkamtibmas()) {
            $query->whereHas('wilayah', fn($q) =>
                $q->where('id', $user->wilayah_id)
                  ->orWhere('parent_id', $user->wilayah_id)
            );
        }
        // dinas, pusat, admin: no wilayah restriction

        // Filters
        if ($request->filled('status')) {
            $query->where('status_validasi', $request->status);
        }
        if ($request->filled('komoditas')) {
            $query->byKomoditas($request->komoditas);
        }
        if ($request->filled('wilayah_id')) {
            $query->byWilayah($request->wilayah_id);
        }
        if ($request->filled('periode')) {
            $query->byPeriode($request->periode);
        }
        if ($request->filled('musim_tanam')) {
            $query->where('musim_tanam', $request->musim_tanam);
        }

        $data = $query->paginate($request->integer('per_page', 15));

        return response()->json([
            'data'  => DataPertanianResource::collection($data),
            'meta'  => [
                'current_page' => $data->currentPage(),
                'last_page'    => $data->lastPage(),
                'per_page'     => $data->perPage(),
                'total'        => $data->total(),
            ],
        ]);
    }

    /**
     * POST /api/data-pertanian
     */
    public function store(StoreDataPertanianRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle foto upload to MinIO/S3
        $fotoUrl = null;
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('foto-lahan/' . date('Y/m'), 's3');
            $fotoUrl = Storage::disk('s3')->url($path);
        }

        $isDraft = $request->boolean('is_draft', false);

        $dataPertanian = DataPertanian::create([
            ...$request->except(['foto', 'is_draft', 'periode']),
            'input_oleh'   => $request->user()->id,
            'foto_url'     => $fotoUrl,
            'is_draft'     => $isDraft,
            'submitted_at' => $isDraft ? null : now(),
            'periode'      => $data['periode'],
        ]);

        AuditLog::record(
            $isDraft ? 'create_draft' : 'submit_data',
            'data_pertanian',
            $dataPertanian->id,
            null,
            $dataPertanian->toArray()
        );

        // Check for anomaly and dispatch alert job
        if (!$isDraft && $dataPertanian->isAnomali()) {
            SendAnomalyAlert::dispatch($dataPertanian);
        }

        return response()->json([
            'message' => $isDraft ? 'Draft berhasil disimpan.' : 'Data pertanian berhasil disubmit.',
            'data'    => new DataPertanianResource($dataPertanian->load(['wilayah', 'inputOleh'])),
        ], 201);
    }

    /**
     * GET /api/data-pertanian/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $dataPertanian = DataPertanian::with(['wilayah.parent', 'inputOleh', 'validasi.validator'])
            ->findOrFail($id);

        // RBAC: aparatur_desa can only see own data
        if ($user->isAparaturDesa() && $dataPertanian->input_oleh !== $user->id) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        return response()->json(new DataPertanianResource($dataPertanian));
    }

    /**
     * PUT /api/data-pertanian/{id}
     * Only allowed if still 'menunggu' or 'ditolak'
     */
    public function update(StoreDataPertanianRequest $request, string $id): JsonResponse
    {
        $dataPertanian = DataPertanian::where('input_oleh', $request->user()->id)->findOrFail($id);

        if ($dataPertanian->status_validasi === 'disetujui') {
            return response()->json([
                'message' => 'Data yang sudah disetujui tidak dapat diubah.',
            ], 422);
        }

        $old = $dataPertanian->toArray();

        // Handle foto re-upload
        $fotoUrl = $dataPertanian->foto_url;
        if ($request->hasFile('foto')) {
            // Delete old photo
            if ($fotoUrl) {
                $oldPath = parse_url($fotoUrl, PHP_URL_PATH);
                Storage::disk('s3')->delete(ltrim($oldPath, '/'));
            }
            $path = $request->file('foto')->store('foto-lahan/' . date('Y/m'), 's3');
            $fotoUrl = Storage::disk('s3')->url($path);
        }

        $isDraft = $request->boolean('is_draft', $dataPertanian->is_draft);

        $dataPertanian->update([
            ...$request->except(['foto', 'is_draft']),
            'foto_url'         => $fotoUrl,
            'is_draft'         => $isDraft,
            'status_validasi'  => 'menunggu', // reset to pending on edit
            'submitted_at'     => $isDraft ? null : now(),
        ]);

        AuditLog::record('update_data', 'data_pertanian', $dataPertanian->id, $old, $dataPertanian->fresh()->toArray());

        return response()->json([
            'message' => 'Data berhasil diperbarui.',
            'data'    => new DataPertanianResource($dataPertanian->load(['wilayah', 'inputOleh'])),
        ]);
    }

    /**
     * DELETE /api/data-pertanian/{id}
     * Only aparatur_desa can delete own draft/ditolak data
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $dataPertanian = DataPertanian::where('input_oleh', $request->user()->id)->findOrFail($id);

        if ($dataPertanian->status_validasi === 'disetujui') {
            return response()->json([
                'message' => 'Data yang sudah disetujui tidak dapat dihapus.',
            ], 422);
        }

        AuditLog::record('delete_data', 'data_pertanian', $dataPertanian->id, $dataPertanian->toArray());

        $dataPertanian->delete();

        return response()->json(['message' => 'Data berhasil dihapus.']);
    }

    /**
     * POST /api/data-pertanian/{id}/submit
     * Convert draft to submission
     */
    public function submit(Request $request, string $id): JsonResponse
    {
        $dataPertanian = DataPertanian::where('input_oleh', $request->user()->id)
            ->where('is_draft', true)
            ->findOrFail($id);

        $dataPertanian->update([
            'is_draft'     => false,
            'submitted_at' => now(),
            'status_validasi' => 'menunggu',
        ]);

        AuditLog::record('submit_draft', 'data_pertanian', $dataPertanian->id);

        // Dispatch notification to bhabinkamtibmas
        \App\Jobs\SendValidasiNotification::dispatch($dataPertanian);

        return response()->json([
            'message' => 'Data berhasil disubmit untuk validasi.',
            'data'    => new DataPertanianResource($dataPertanian->load('wilayah')),
        ]);
    }
}
