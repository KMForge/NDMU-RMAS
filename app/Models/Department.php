<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['college_id', 'code', 'name', 'is_active'])]
class Department extends Model
{
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    public function facultyProfiles(): HasMany
    {
        return $this->hasMany(FacultyProfile::class);
    }

    public function assignedFacultyProfiles(): BelongsToMany
    {
        return $this->belongsToMany(FacultyProfile::class, 'faculty_profile_departments')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
