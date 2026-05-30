<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataPertanian;
use App\Models\User;
use App\Models\Wilayah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     * Role-aware dashboard stats
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return match ($user->role) {
            'aparatur_desa'    => $this->aparaturDesaDashboard($user),
            'bhabinkamtibmas'  => $this->bhabinkamtibmasDashboard($user),
            'dinas', 'pusat', 'admin' => $this->dinasDashboard($request),
        };
    }

    // ─── Aparatur Desa ───────────────────────────────────────────────────────

    private function aparaturDesaDashboard($user): JsonResponse
    {
        $baseQuery = DataPertanian::where('input_oleh', $user->id)->submitted();

        $stats = [
            'total_lahan_ha'      => (float) ($baseQuery->clone()->sum('luas_lahan_ha') ?? 0),
            'laporan_bulan_ini'   => $baseQuery->clone()->whereMonth('created_at', now()->month)->count(),
            'menunggu'            => $baseQuery->clone()->menunggu()->count(),
            'disetujui'           => $baseQuery->clone()->disetujui()->count(),
            'ditolak'             => $baseQuery->clone()->ditolak()->count(),
            'draft'               => DataPertanian::where('input_oleh', $user->id)->draft()->count(),
        ];

        $recentData = DataPertanian::with(['validasiTerakhir'])
            ->where('input_oleh', $user->id)
            ->submitted()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'id'             => $d->id,
                'komoditas'      => $d->komoditas,
                'luas_lahan_ha'  => (float) $d->luas_lahan_ha,
                'status'         => $d->status_validasi,
                'periode'        => $d->periode?->format('Y-m'),
                'catatan_tolak'  => $d->status_validasi === 'ditolak'
                    ? $d->validasiTerakhir?->catatan : null,
            ]);

        return response()->json(compact('stats', 'recentData'));
    }

    // ─── Bhabinkamtibmas ─────────────────────────────────────────────────────

    private function bhabinkamtibmasDashboard($user): JsonResponse
    {
        $wilayahQuery = fn($q) =>
            $q->where('wilayah_id', $user->wilayah_id)
              ->orWhereHas('wilayah', fn($wq) => $wq->where('parent_id', $user->wilayah_id));

        $stats = [
            'antrian_validasi'    => DataPertanian::menunggu()->submitted()->where($wilayahQuery)->count(),
            'divalidasi_hari_ini' => DataPertanian::disetujui()->submitted()->where($wilayahQuery)
                ->whereDate('updated_at', today())->count(),
            'total_disetujui'     => DataPertanian::disetujui()->submitted()->where($wilayahQuery)->count(),
            'total_ditolak'       => DataPertanian::ditolak()->submitted()->where($wilayahQuery)->count(),
        ];

        return response()->json(compact('stats'));
    }

    // ─── Dinas / Pusat / Admin ───────────────────────────────────────────────

    private function dinasDashboard(Request $request): JsonResponse
    {
        $wilayahId = $request->input('wilayah_id');
        $cacheKey  = 'dashboard:dinas:' . ($wilayahId ?? 'nasional');

        $data = Cache::remember($cacheKey, 300, function () use ($wilayahId) {
            $base = DataPertanian::disetujui();
            if ($wilayahId) $base->where('wilayah_id', $wilayahId);

            // Agregasi per komoditas
            $perKomoditas = (clone $base)->select('komoditas',
                    DB::raw('SUM(luas_lahan_ha) as total_lahan'),
                    DB::raw('SUM(estimasi_panen_ton) as total_panen'),
                    DB::raw('COUNT(*) as jumlah_laporan')
                )
                ->groupBy('komoditas')
                ->orderByDesc('total_panen')
                ->get();

            // Tren bulanan 12 bulan terakhir
            $trenBulanan = (clone $base)->select(
                    DB::raw("TO_CHAR(periode, 'YYYY-MM') as bulan"),
                    DB::raw('SUM(estimasi_panen_ton) as total_panen'),
                    DB::raw('SUM(luas_lahan_ha) as total_lahan')
                )
                ->where('periode', '>=', now()->subMonths(12)->startOfMonth())
                ->groupBy(DB::raw("TO_CHAR(periode, 'YYYY-MM')"))
                ->orderBy('bulan')
                ->get();

            // Anomali
            $anomali = DataPertanian::where('status_validasi', 'menunggu')
                ->where('estimasi_panen_ton', '>', 0)
                ->get()
                ->filter(fn($d) => $d->isAnomali())
                ->take(10)
                ->map(fn($d) => [
                    'id'           => $d->id,
                    'komoditas'    => $d->komoditas,
                    'wilayah_id'   => $d->wilayah_id,
                    'estimasi_ton' => (float) $d->estimasi_panen_ton,
                ]);

            // Summary stats
            $stats = [
                'total_lahan_ha'      => (float) (clone $base)->sum('luas_lahan_ha'),
                'total_panen_ton'     => (float) (clone $base)->sum('estimasi_panen_ton'),
                'total_laporan'       => (clone $base)->count(),
                'total_desa_aktif'    => DataPertanian::disetujui()->distinct('wilayah_id')->count('wilayah_id'),
                'menunggu_validasi'   => DataPertanian::menunggu()->submitted()->count(),
            ];

            return compact('stats', 'perKomoditas', 'trenBulanan', 'anomali');
        });

        return response()->json($data);
    }

    /**
     * GET /api/dashboard/peta
     * Heatmap data per wilayah
     */
    public function peta(Request $request): JsonResponse
    {
        $level = $request->input('level', 'kabupaten');
        $komoditas = $request->input('komoditas');

        $cacheKey = 'peta:' . $level . ':' . ($komoditas ?? 'all');

        $data = Cache::remember($cacheKey, 600, function () use ($level, $komoditas) {
            $query = DataPertanian::disetujui()
                ->select(
                    'wilayah_id',
                    DB::raw('SUM(luas_lahan_ha) as total_lahan'),
                    DB::raw('SUM(estimasi_panen_ton) as total_panen'),
                    DB::raw('COUNT(*) as jumlah_laporan')
                )
                ->with('wilayah')
                ->groupBy('wilayah_id');

            if ($komoditas) {
                $query->byKomoditas($komoditas);
            }

            return $query->get()->map(fn($d) => [
                'wilayah_id'    => $d->wilayah_id,
                'nama'          => $d->wilayah?->nama,
                'koordinat_lat' => $d->wilayah?->koordinat_lat,
                'koordinat_lng' => $d->wilayah?->koordinat_lng,
                'total_lahan'   => (float) $d->total_lahan,
                'total_panen'   => (float) $d->total_panen,
                'jumlah_laporan' => $d->jumlah_laporan,
            ]);
        });

        return response()->json(['data' => $data]);
    }

    /**
     * GET /api/dashboard/statistik-nasional
     * Quick landing page stats (public)
     */
    public function statistikNasional(): JsonResponse
    {
        $stats = Cache::remember('statistik:nasional', 3600, fn() => [
            'desa_terdaftar'    => User::where('role', 'aparatur_desa')->count(),
            'data_tervalidasi'  => DataPertanian::disetujui()->count(),
            'pengguna_aktif'    => User::where('is_active', true)->count(),
            'total_lahan_ha'    => (float) DataPertanian::disetujui()->sum('luas_lahan_ha'),
            'total_panen_ton'   => (float) DataPertanian::disetujui()->sum('estimasi_panen_ton'),
        ]);

        return response()->json($stats);
    }
}
