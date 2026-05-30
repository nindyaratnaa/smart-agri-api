<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DataPertanian;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LaporanController extends Controller
{
    /**
     * GET /api/laporan/export
     * Export data as CSV/Excel/PDF
     */
    public function export(Request $request)
    {
        $request->validate([
            'format'     => 'required|in:csv,pdf',
            'periode'    => 'nullable|date_format:Y-m',
            'wilayah_id' => 'nullable|uuid|exists:wilayah,id',
            'komoditas'  => 'nullable|string|max:100',
            'status'     => 'nullable|in:menunggu,disetujui,ditolak',
        ]);

        $query = DataPertanian::with(['wilayah', 'inputOleh', 'validasiTerakhir.validator'])
            ->submitted();

        if ($request->filled('periode'))    $query->byPeriode($request->periode);
        if ($request->filled('wilayah_id')) $query->byWilayah($request->wilayah_id);
        if ($request->filled('komoditas'))  $query->byKomoditas($request->komoditas);
        if ($request->filled('status'))     $query->where('status_validasi', $request->status);

        $data = $query->orderBy('periode')->orderBy('created_at')->get();

        AuditLog::record('export_laporan', null, null, null, [
            'format' => $request->format,
            'filters' => $request->only(['periode', 'wilayah_id', 'komoditas', 'status']),
            'total_records' => $data->count(),
        ]);

        if ($request->format === 'csv') {
            return $this->exportCsv($data);
        }

        return $this->exportPdf($data, $request);
    }

    private function exportCsv($data)
    {
        $filename  = 'laporan-pertanian-' . now()->format('Ymd-His') . '.csv';
        $headers   = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, [
                'ID', 'Wilayah', 'Input Oleh', 'Komoditas', 'Varietas',
                'Luas Lahan (Ha)', 'Estimasi Panen (Ton)', 'Musim Tanam',
                'Periode', 'Status Validasi', 'Catatan Tolak',
                'Validator', 'Tanggal Validasi', 'Tanggal Input',
            ]);

            foreach ($data as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->wilayah?->nama,
                    $row->inputOleh?->name,
                    $row->komoditas,
                    $row->varietas,
                    $row->luas_lahan_ha,
                    $row->estimasi_panen_ton,
                    $row->musim_tanam,
                    $row->periode?->format('Y-m'),
                    $row->status_validasi,
                    $row->status_validasi === 'ditolak' ? $row->validasiTerakhir?->catatan : '',
                    $row->validasiTerakhir?->validator?->name,
                    $row->validasiTerakhir?->validated_at?->format('Y-m-d H:i:s'),
                    $row->created_at?->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportPdf($data, Request $request)
    {
        $pdf = Pdf::loadView('laporan.pertanian-pdf', [
            'data'     => $data,
            'filters'  => $request->only(['periode', 'komoditas', 'status']),
            'generated_at' => now()->isoFormat('DD MMMM YYYY HH:mm'),
            'nomorLaporan' => 'SA-' . now()->format('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT),
            'total_lahan'  => $data->sum('luas_lahan_ha'),
            'total_panen'  => $data->sum('estimasi_panen_ton'),
        ])->setPaper('a4', 'landscape');

        $filename = 'laporan-pertanian-' . now()->format('Ymd-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * GET /api/laporan/ringkasan
     * Summary stats for laporan page
     */
    public function ringkasan(Request $request): JsonResponse
    {
        $request->validate([
            'tahun'      => 'nullable|integer|digits:4',
            'wilayah_id' => 'nullable|uuid',
        ]);

        $tahun = $request->integer('tahun', now()->year);

        $query = DataPertanian::disetujui()
            ->whereYear('periode', $tahun);

        if ($request->filled('wilayah_id')) {
            $query->where('wilayah_id', $request->wilayah_id);
        }

        $perMusim = (clone $query)->select(
                'musim_tanam',
                DB::raw('SUM(luas_lahan_ha) as total_lahan'),
                DB::raw('SUM(estimasi_panen_ton) as total_panen'),
                DB::raw('COUNT(*) as jumlah')
            )
            ->groupBy('musim_tanam')
            ->get();

        $perKomoditas = (clone $query)->select(
                'komoditas',
                DB::raw('SUM(luas_lahan_ha) as total_lahan'),
                DB::raw('SUM(estimasi_panen_ton) as total_panen'),
                DB::raw('COUNT(DISTINCT wilayah_id) as jumlah_desa')
            )
            ->groupBy('komoditas')
            ->orderByDesc('total_panen')
            ->get();

        return response()->json([
            'tahun'         => $tahun,
            'per_musim'     => $perMusim,
            'per_komoditas' => $perKomoditas,
            'totals' => [
                'lahan_ha'  => (float) (clone $query)->sum('luas_lahan_ha'),
                'panen_ton' => (float) (clone $query)->sum('estimasi_panen_ton'),
                'laporan'   => (clone $query)->count(),
            ],
        ]);
    }

    /**
     * GET /api/laporan/audit
     * Audit log (admin only)
     */
    public function auditLog(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => 'nullable|uuid',
            'aksi'         => 'nullable|string',
            'tabel_target' => 'nullable|string',
            'from'         => 'nullable|date',
            'to'           => 'nullable|date',
        ]);

        $query = AuditLog::with('user')
            ->orderByDesc('created_at');

        if ($request->filled('user_id'))      $query->where('user_id', $request->user_id);
        if ($request->filled('aksi'))         $query->where('aksi', 'like', '%' . $request->aksi . '%');
        if ($request->filled('tabel_target')) $query->where('tabel_target', $request->tabel_target);
        if ($request->filled('from'))         $query->where('created_at', '>=', $request->from);
        if ($request->filled('to'))           $query->where('created_at', '<=', $request->to . ' 23:59:59');

        $logs = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => $logs->map(fn($log) => [
                'id'           => $log->id,
                'user'         => $log->user ? ['id' => $log->user->id, 'name' => $log->user->name] : null,
                'aksi'         => $log->aksi,
                'tabel_target' => $log->tabel_target,
                'record_id'    => $log->record_id,
                'data_lama'    => $log->data_lama,
                'data_baru'    => $log->data_baru,
                'ip_address'   => $log->ip_address,
                'created_at'   => $log->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'total'        => $logs->total(),
            ],
        ]);
    }
}
