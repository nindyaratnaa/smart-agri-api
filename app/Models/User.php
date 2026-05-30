<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'wilayah_id',
        'is_active',
        'nik',
        'jabatan',
        'no_hp',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    public function dataPertanian(): HasMany
    {
        return $this->hasMany(DataPertanian::class, 'input_oleh');
    }

    public function validasi(): HasMany
    {
        return $this->hasMany(Validasi::class, 'validator_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ─── Role helpers ────────────────────────────────────────────────────────

    public function isAparaturDesa(): bool  { return $this->role === 'aparatur_desa'; }
    public function isBhabinkamtibmas(): bool { return $this->role === 'bhabinkamtibmas'; }
    public function isDinas(): bool         { return $this->role === 'dinas'; }
    public function isPusat(): bool         { return $this->role === 'pusat'; }
    public function isAdmin(): bool         { return $this->role === 'admin'; }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles);
    }

    public function canViewNationalData(): bool
    {
        return in_array($this->role, ['dinas', 'pusat', 'admin']);
    }
}
