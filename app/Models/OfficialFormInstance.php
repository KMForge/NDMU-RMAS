<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'official_form_definition_id',
    'research_class_group_id',
    'research_class_id',
    'context_key',
    'source_type',
    'source_id',
    'initiated_by',
    'status',
    'current_version_id',
    'defense_evaluation_id',
])]
class OfficialFormInstance extends Model
{
    public function definition(): BelongsTo
    {
        return $this->belongsTo(OfficialFormDefinition::class, 'official_form_definition_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function researchClass(): BelongsTo
    {
        return $this->belongsTo(ResearchClass::class, 'research_class_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(OfficialFormVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(OfficialFormVersion::class, 'official_form_instance_id');
    }

    public function actorAssignments(): HasMany
    {
        return $this->hasMany(OfficialFormActorAssignment::class, 'official_form_instance_id');
    }

    public function defenseEvaluation(): BelongsTo
    {
        return $this->belongsTo(DefenseEvaluation::class, 'defense_evaluation_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function titlePresentation(): HasOne
    {
        return $this->hasOne(TitlePresentation::class, 'official_form_instance_id');
    }
}
