<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Validasi extends Model
{
    use HasUuids;

    protected $table = 'validasi';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'data_pertanian_id',
        'validator_id',
        'keputusan',
        'catatan',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function dataPertanian(): BelongsTo
    {
        return $this->belongsTo(DataPertanian::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeDisetujui($query) { return $query->where('keputusan', 'disetujui'); }
    public function scopeDitolak($query)   { return $query->where('keputusan', 'ditolak'); }
}
