<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['research_class_group_id', 'research_class_id', 'research_class_enrollment_id', 'student_id', 'assigned_by', 'joined_at', 'archived_at', 'archive_reason'])]
class ResearchClassGroupMemberHistory extends Model
{
    public function group(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class, 'research_class_group_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ResearchClassEnrollment::class, 'research_class_enrollment_id');
    }

    protected function casts(): array
    {
        return ['joined_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }
}
