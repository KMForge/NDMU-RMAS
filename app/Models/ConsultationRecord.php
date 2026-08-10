<?php

namespace App\Models;

use App\Enums\ConsultationMode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'consultation_request_id',
    'research_class_group_id',
    'conducted_by',
    'consulted_at',
    'duration_minutes',
    'consultation_mode',
    'location',
    'meeting_url',
    'agenda',
    'discussion',
    'recommendations',
    'next_consultation_at',
    'supersedes_record_id',
    'is_superseded',
    'correction_reason',
])]
class ConsultationRecord extends Model
{
    public function request(): BelongsTo
    {
        return $this->belongsTo(ConsultationRequest::class, 'consultation_request_id');
    }

    public function researchClassGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function conductedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_record_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ConsultationAttendance::class, 'consultation_record_id');
    }

    protected function casts(): array
    {
        return [
            'consulted_at' => 'immutable_datetime',
            'next_consultation_at' => 'immutable_datetime',
            'consultation_mode' => ConsultationMode::class,
            'is_superseded' => 'boolean',
        ];
    }
}
