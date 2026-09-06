<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['department_id', 'code', 'name', 'degree_level', 'is_active'])]
class Program extends Model
{
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function studentProfiles(): HasMany
    {
        return $this->hasMany(StudentProfile::class);
    }
}
