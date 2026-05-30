<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    // Immutable — no updates, no deletes
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'aksi',
        'tabel_target',
        'record_id',
        'data_lama',
        'data_baru',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'data_lama'  => 'array',
            'data_baru'  => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Static helpers ──────────────────────────────────────────────────────

    public static function record(
        string $aksi,
        ?string $tabelTarget = null,
        ?string $recordId = null,
        ?array $dataLama = null,
        ?array $dataBaru = null
    ): self {
        return self::create([
            'user_id'      => auth()->id(),
            'aksi'         => $aksi,
            'tabel_target' => $tabelTarget,
            'record_id'    => $recordId,
            'data_lama'    => $dataLama,
            'data_baru'    => $dataBaru,
            'ip_address'   => request()->ip(),
            'user_agent'   => request()->userAgent(),
            'created_at'   => now(),
        ]);
    }
}
