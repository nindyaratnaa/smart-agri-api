<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DataPertanianController;
use App\Http\Controllers\Api\LaporanController;
use App\Http\Controllers\Api\ValidasiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Smart Agriculture API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (configured in bootstrap/app.php)
|
| Role values: aparatur_desa | bhabinkamtibmas | dinas | pusat | admin
|
*/

// ─── Public ──────────────────────────────────────────────────────────────────

Route::get('/health', fn() => response()->json([
    'status'  => 'ok',
    'service' => config('app.name'),
    'time'    => now()->toIso8601String(),
]));

Route::get('/statistik-nasional', [DashboardController::class, 'statistikNasional']);

// ─── Auth ─────────────────────────────────────────────────────────────────────

Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login'])
        ->middleware('throttle:10,1'); // 10 req/min

    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me',           [AuthController::class, 'me']);
        Route::put('/profile',      [AuthController::class, 'updateProfile']);
        Route::post('/logout',      [AuthController::class, 'logout']);
        Route::post('/refresh',     [AuthController::class, 'refresh']);
    });
});

// ─── Protected ───────────────────────────────────────────────────────────────

Route::middleware(['auth:sanctum'])->group(function () {

    // ── Dashboard (role-aware) ───────────────────────────────────────────────
    Route::get('/dashboard',       [DashboardController::class, 'index']);
    Route::get('/dashboard/peta',  [DashboardController::class, 'peta'])
        ->middleware('role:dinas,pusat,admin');

    // ── Data Pertanian ───────────────────────────────────────────────────────
    Route::prefix('data-pertanian')->group(function () {
        Route::get('/',        [DataPertanianController::class, 'index']);
        Route::post('/',       [DataPertanianController::class, 'store'])
            ->middleware('role:aparatur_desa');
        Route::get('/{id}',    [DataPertanianController::class, 'show']);
        Route::put('/{id}',    [DataPertanianController::class, 'update'])
            ->middleware('role:aparatur_desa');
        Route::delete('/{id}', [DataPertanianController::class, 'destroy'])
            ->middleware('role:aparatur_desa');
        Route::post('/{id}/submit', [DataPertanianController::class, 'submit'])
            ->middleware('role:aparatur_desa');
    });

    // ── Validasi ─────────────────────────────────────────────────────────────
    Route::prefix('validasi')->group(function () {
        Route::get('/antrian',  [ValidasiController::class, 'antrian'])
            ->middleware('role:bhabinkamtibmas,admin');
        Route::post('/{id}',    [ValidasiController::class, 'validasi'])
            ->middleware('role:bhabinkamtibmas');
        Route::get('/riwayat',  [ValidasiController::class, 'riwayat'])
            ->middleware('role:bhabinkamtibmas,dinas,pusat,admin');
    });

    // ── Laporan ──────────────────────────────────────────────────────────────
    Route::prefix('laporan')->group(function () {
        Route::get('/export',   [LaporanController::class, 'export'])
            ->middleware('role:dinas,pusat,admin');
        Route::get('/ringkasan', [LaporanController::class, 'ringkasan'])
            ->middleware('role:dinas,pusat,admin');
        Route::get('/audit',    [LaporanController::class, 'auditLog'])
            ->middleware('role:admin');
    });

    // ── Wilayah (lookup) ─────────────────────────────────────────────────────
    Route::prefix('wilayah')->group(function () {
        Route::get('/', fn(\Illuminate\Http\Request $req) =>
            response()->json(
                \App\Models\Wilayah::when($req->level, fn($q) => $q->byLevel($req->level))
                    ->when($req->parent_id, fn($q) => $q->where('parent_id', $req->parent_id))
                    ->orderBy('nama')
                    ->get(['id', 'kode', 'nama', 'level', 'parent_id', 'koordinat_lat', 'koordinat_lng'])
            )
        );

        Route::get('/{id}', fn(string $id) =>
            response()->json(\App\Models\Wilayah::with('parent', 'children')->findOrFail($id))
        );
    });

    // ── User Management (Admin only) ─────────────────────────────────────────
    Route::prefix('users')->middleware('role:admin')->group(function () {
        Route::get('/', fn(\Illuminate\Http\Request $req) =>
            response()->json(
                \App\Models\User::with('wilayah')
                    ->when($req->role, fn($q) => $q->where('role', $req->role))
                    ->when($req->search, fn($q) => $q->where('name', 'ilike', '%' . $req->search . '%')
                        ->orWhere('email', 'ilike', '%' . $req->search . '%'))
                    ->paginate($req->integer('per_page', 15))
            )
        );

        Route::post('/', [AuthController::class, 'register']);

        Route::patch('/{id}/toggle-active', function (string $id) {
            $user = \App\Models\User::findOrFail($id);
            $user->update(['is_active' => !$user->is_active]);
            \App\Models\AuditLog::record(
                $user->is_active ? 'activate_user' : 'deactivate_user',
                'users',
                $user->id
            );
            return response()->json([
                'message'   => 'Status akun berhasil diubah.',
                'is_active' => $user->is_active,
            ]);
        });
    });
});
