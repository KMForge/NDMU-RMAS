<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['user_id', 'department_id', 'employee_number', 'academic_rank', 'specialization', 'contact_number'])]
class FacultyProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * All departments in which this faculty member may teach or serve.
     * The department_id column remains the required primary/home department.
     */
    public function departments(): BelongsToMany
    {
        return $this->belongsToMany(Department::class, 'faculty_profile_departments')
            ->withTimestamps();
    }
}
