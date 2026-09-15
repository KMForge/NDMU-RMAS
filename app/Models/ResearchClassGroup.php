<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'research_class_id',
    'leader_student_id',
    'research_group_id',
    'creation_token',
    'name',
    'adviser_id',
    'created_by',
    'status',
    'disbanded_at',
])]
class ResearchClassGroup extends Model
{
    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class);
    }

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_student_id');
    }

    public function researchGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchGroup::class);
    }

    public function adviser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'adviser_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): HasMany
    {
        return $this->hasMany(ResearchClassGroupMember::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function defenses(): HasMany
    {
        return $this->hasMany(Defense::class, 'research_class_group_id');
    }

    public function panelCommittees(): HasMany
    {
        return $this->hasMany(ResearchGroupPanelCommittee::class, 'research_class_group_id');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ResearchGroupMilestone::class);
    }

    public function memberHistories(): HasMany
    {
        return $this->hasMany(ResearchClassGroupMemberHistory::class);
    }

    public function adviserRequests(): HasMany
    {
        return $this->hasMany(ResearchClassGroupAdviserRequest::class);
    }

    public function adviserHistories(): HasMany
    {
        return $this->hasMany(ResearchClassGroupAdviserHistory::class);
    }

    public function adviserChangeRequests(): HasMany
    {
        return $this->hasMany(ResearchGroupAdviserChangeRequest::class);
    }

    public function consultationRecords(): HasMany
    {
        return $this->hasMany(ConsultationRecord::class, 'research_class_group_id');
    }

    public function getTitleAttribute(): ?string
    {
        if ($this->research_group_id !== null) {
            $title = DB::table('research_projects')
                ->where('research_group_id', $this->research_group_id)
                ->value('title');
            if (! empty($title)) {
                return $title;
            }
        }

        $res026 = OfficialFormInstance::query()
            ->where('research_class_group_id', $this->id)
            ->whereHas('definition', fn ($q) => $q->where('code', 'RES-026'))
            ->with(['titlePresentation.formVersion'])
            ->latest('id')
            ->first();

        if ($res026?->titlePresentation?->approved_title_number) {
            $topics = $res026->titlePresentation->formVersion?->payload['topics'] ?? [];
            $num = (int) $res026->titlePresentation->approved_title_number;
            $title = trim((string) ($topics[$num - 1] ?? ''));
            if (! empty($title)) {
                return $title;
            }
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->disbanded_at === null;
    }

    public function isLeader(?User $user): bool
    {
        if ($user === null || ! $this->isActive()) {
            return false;
        }

        return $this->leader_student_id === $user->getKey();
    }

    protected function casts(): array
    {
        return [
            'disbanded_at' => 'immutable_datetime',
        ];
    }
}
