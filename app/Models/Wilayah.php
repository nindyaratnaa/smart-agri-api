<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wilayah extends Model
{
    use HasUuids;

    protected $table = 'wilayah';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'kode',
        'nama',
        'level',
        'parent_id',
        'koordinat_lat',
        'koordinat_lng',
    ];

    protected function casts(): array
    {
        return [
            'koordinat_lat' => 'decimal:8',
            'koordinat_lng' => 'decimal:8',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Wilayah::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function dataPertanian(): HasMany
    {
        return $this->hasMany(DataPertanian::class);
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeByLevel($query, string $level)
    {
        return $query->where('level', $level);
    }

    public function scopeDesa($query) { return $query->byLevel('desa'); }
    public function scopeKecamatan($query) { return $query->byLevel('kecamatan'); }
    public function scopeKabupaten($query) { return $query->byLevel('kabupaten'); }
    public function scopeProvinsi($query) { return $query->byLevel('provinsi'); }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Get full hierarchy path: Desa → Kecamatan → Kabupaten → Provinsi
     */
    public function getFullPathAttribute(): string
    {
        $parts = [$this->nama];
        $current = $this;
        while ($current->parent_id) {
            $current = $current->parent;
            $parts[] = $current->nama;
        }
        return implode(', ', $parts);
    }
}
