<?php

namespace App\Models;

use App\Enums\ConsultationMode;
use App\Enums\ConsultationStatus;
use App\Enums\DocumentStage;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'research_class_group_id',
    'assigned_adviser_id',
    'research_project_id',
    'adviser_assignment_id',
    'requested_by',
    'request_token',
    'preferred_at',
    'confirmed_start_at',
    'confirmed_end_at',
    'duration_minutes',
    'consultation_mode',
    'location',
    'meeting_url',
    'document_stage',
    'document_id',
    'agenda',
    'status',
    'reviewed_by',
    'reviewed_at',
    'review_notes',
    'cancelled_by',
    'cancelled_at',
    'cancellation_reason',
])]
class ConsultationRequest extends Model
{
    public function researchClassGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function assignedAdviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_adviser_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function relatedDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(ConsultationScheduleProposal::class, 'consultation_request_id');
    }

    public function latestProposal(): HasOne
    {
        return $this->hasOne(ConsultationScheduleProposal::class, 'consultation_request_id')->latestOfMany();
    }

    public function records(): HasMany
    {
        return $this->hasMany(ConsultationRecord::class, 'consultation_request_id');
    }

    protected function casts(): array
    {
        return [
            'preferred_at' => 'immutable_datetime',
            'confirmed_start_at' => 'immutable_datetime',
            'confirmed_end_at' => 'immutable_datetime',
            'reviewed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'consultation_mode' => ConsultationMode::class,
            'status' => ConsultationStatus::class,
            'document_stage' => DocumentStage::class,
        ];
    }
}
