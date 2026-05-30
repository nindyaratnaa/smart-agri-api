<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataPertanian extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'data_pertanian';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'wilayah_id',
        'input_oleh',
        'komoditas',
        'varietas',
        'luas_lahan_ha',
        'estimasi_panen_ton',
        'musim_tanam',
        'status_validasi',
        'foto_url',
        'periode',
        'koordinat_lat',
        'koordinat_lng',
        'catatan_input',
        'is_draft',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'luas_lahan_ha'     => 'decimal:2',
            'estimasi_panen_ton' => 'decimal:2',
            'koordinat_lat'     => 'decimal:8',
            'koordinat_lng'     => 'decimal:8',
            'periode'           => 'date',
            'submitted_at'      => 'datetime',
            'is_draft'          => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function inputOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'input_oleh');
    }

    public function validasi(): HasMany
    {
        return $this->hasMany(Validasi::class);
    }

    public function validasiTerakhir(): HasOne
    {
        return $this->hasOne(Validasi::class)->latestOfMany();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeMenunggu($query)    { return $query->where('status_validasi', 'menunggu'); }
    public function scopeDisetujui($query)   { return $query->where('status_validasi', 'disetujui'); }
    public function scopeDitolak($query)     { return $query->where('status_validasi', 'ditolak'); }
    public function scopeSubmitted($query)   { return $query->where('is_draft', false); }
    public function scopeDraft($query)       { return $query->where('is_draft', true); }

    public function scopeByWilayah($query, string $wilayahId)
    {
        return $query->where('wilayah_id', $wilayahId);
    }

    public function scopeByPeriode($query, string $periode)
    {
        return $query->whereYear('periode', substr($periode, 0, 4))
                     ->whereMonth('periode', substr($periode, 5, 2));
    }

    public function scopeByKomoditas($query, string $komoditas)
    {
        return $query->where('komoditas', $komoditas);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isAnomali(float $threshold = 0.3): bool
    {
        // Check if this record deviates > threshold from historical average
        $avg = self::where('wilayah_id', $this->wilayah_id)
            ->where('komoditas', $this->komoditas)
            ->where('musim_tanam', $this->musim_tanam)
            ->where('id', '!=', $this->id)
            ->whereYear('periode', now()->year - 1)
            ->avg('estimasi_panen_ton');

        if (!$avg) return false;

        return abs($this->estimasi_panen_ton - $avg) / $avg > $threshold;
    }
}
