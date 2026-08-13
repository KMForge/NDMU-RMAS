<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

#[Fillable([
    'facilitator_id',
    'creation_token',
    'name',
    'description',
    'max_students',
    'is_active',
])]
#[Hidden(['creation_token', 'join_code_hash', 'join_code_encrypted'])]
class ResearchClass extends Model
{
    public function facilitator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'facilitator_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ResearchClassEnrollment::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(ResearchClassGroup::class);
    }

    public function officialFormActorAssignments(): HasMany
    {
        return $this->hasMany(ResearchClassActorAssignment::class);
    }

    public function setJoinCode(string $joinCode): void
    {
        $normalized = self::normalizeJoinCode($joinCode);
        $this->join_code_hash = self::joinCodeFingerprint($normalized);
        $this->join_code_encrypted = Crypt::encryptString($normalized);
    }

    public function revealJoinCode(): string
    {
        return Crypt::decryptString($this->join_code_encrypted);
    }

    public static function normalizeJoinCode(string $joinCode): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $joinCode) ?? '');
    }

    public static function joinCodeFingerprint(string $joinCode): string
    {
        return hash_hmac('sha256', self::normalizeJoinCode($joinCode), (string) config('app.key'));
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'max_students' => 'integer',
        ];
    }
}
