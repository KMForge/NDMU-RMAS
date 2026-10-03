<?php

namespace App\Modules\UserManagement\Actions;

use App\Models\Department;
use App\Models\FacultyProfile;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageFacultyDepartmentAssignments
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    /** @param list<int> $departmentIds */
    public function handle(User $faculty, int $primaryDepartmentId, array $departmentIds, User $actor): FacultyProfile
    {
        return DB::transaction(function () use ($faculty, $primaryDepartmentId, $departmentIds, $actor): FacultyProfile {
            $profile = FacultyProfile::query()
                ->with('departments:id,code,name,college_id')
                ->where('user_id', $faculty->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $ids = collect($departmentIds)
                ->map(fn ($id): int => (int) $id)
                ->push($primaryDepartmentId)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            $departments = Department::query()
                ->whereIn('id', $ids)
                ->where('is_active', true)
                ->get(['id', 'code', 'name', 'college_id']);
            $primary = $departments->firstWhere('id', $primaryDepartmentId);

            if ($primary === null || $departments->count() !== $ids->count()) {
                throw ValidationException::withMessages([
                    'facultyDepartmentIds' => 'Every assigned department must be active and valid.',
                ]);
            }

            if ($departments->pluck('college_id')->unique()->count() !== 1) {
                throw ValidationException::withMessages([
                    'facultyDepartmentIds' => 'All teaching departments must belong to the same college.',
                ]);
            }

            $oldValues = [
                'primary_department_id' => $profile->department_id,
                'department_ids' => $profile->departments->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all(),
            ];

            $profile->update(['department_id' => $primaryDepartmentId]);
            $profile->departments()->sync($ids->all());
            $faculty->forceFill(['department' => $primary->name])->save();

            $this->auditLogs->write(
                actor: $actor,
                event: 'user.access-updated',
                description: 'Faculty primary and teaching department assignments were updated.',
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $faculty,
                subjectName: $faculty->name,
                subjectEmail: $faculty->email,
                oldValues: $oldValues,
                newValues: [
                    'primary_department_id' => $primaryDepartmentId,
                    'department_ids' => $ids->sort()->values()->all(),
                ],
                actorContext: 'administrator',
            );

            return $profile->refresh()->load('departments');
        }, 3);
    }
}
