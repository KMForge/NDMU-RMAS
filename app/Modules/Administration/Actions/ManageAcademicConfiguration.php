<?php

namespace App\Modules\Administration\Actions;

use App\Models\College;
use App\Models\DefenseRoom;
use App\Models\Department;
use App\Models\OfficialFormDefinition;
use App\Models\Program;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ManageAcademicConfiguration
{
    /** @var list<string> */
    public const TERMINAL_FORM_STATUSES = ['approved', 'completed', 'complete', 'finalized', 'rejected', 'cancelled'];

    /** @var list<string> */
    public const ACTIVE_DEFENSE_STATUSES = ['current', 'scheduled', 'rescheduled', 'in_progress'];

    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    /** @param array{code: string, name: string} $values */
    public function createDepartment(User $actor, College $college, array $values): Department
    {
        return $this->create($actor, new Department, [
            'college_id' => $college->getKey(),
            'code' => $values['code'],
            'name' => $values['name'],
            'is_active' => true,
        ], 'academic.department.created', 'academic department');
    }

    /** @param array{code: string, name: string, degree_level: string|null} $values */
    public function createProgram(User $actor, Department $department, array $values): Program
    {
        return $this->create($actor, new Program, [
            'department_id' => $department->getKey(),
            'code' => $values['code'],
            'name' => $values['name'],
            'degree_level' => $values['degree_level'],
            'is_active' => true,
        ], 'academic.program.created', 'academic program');
    }

    /** @param array{code: string, name: string, location_notes: string|null} $values */
    public function createDefenseRoom(User $actor, array $values): DefenseRoom
    {
        return $this->create($actor, new DefenseRoom, [
            ...$values,
            'is_active' => true,
        ], 'defense-room.created', 'defense room');
    }

    public function setDepartmentActive(User $actor, Department $department, bool $active): Department
    {
        return $this->setActive($actor, $department, $active, 'academic.department.status-updated', 'academic department');
    }

    public function setProgramActive(User $actor, Program $program, bool $active): Program
    {
        return $this->setActive($actor, $program, $active, 'academic.program.status-updated', 'academic program');
    }

    public function setDefenseRoomActive(User $actor, DefenseRoom $room, bool $active): DefenseRoom
    {
        return $this->setActive($actor, $room, $active, 'defense-room.status-updated', 'defense room');
    }

    public function setOfficialFormActive(User $actor, OfficialFormDefinition $definition, bool $active): OfficialFormDefinition
    {
        return $this->setActive($actor, $definition, $active, 'official-form.definition-status-updated', 'official form definition');
    }

    /** @param array<string, mixed> $values */
    private function create(User $actor, Model $model, array $values, string $event, string $subject): Model
    {
        abort_unless($actor->can('settings.manage'), 403);

        return DB::transaction(function () use ($actor, $model, $values, $event, $subject): Model {
            $model->fill($values)->save();
            $this->auditLogs->write(
                actor: $actor,
                event: $event,
                description: "Created {$subject} {$model->getAttribute('code')}.",
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $model,
                subjectName: (string) $model->getAttribute('name'),
                newValues: $model->only(array_keys($values)),
                actorContext: 'administrator',
            );

            return $model;
        });
    }

    private function setActive(User $actor, Model $model, bool $active, string $event, string $subject): Model
    {
        abort_unless($actor->can('settings.manage'), 403);

        return DB::transaction(function () use ($actor, $model, $active, $event, $subject): Model {
            $locked = $model->newQuery()->lockForUpdate()->findOrFail($model->getKey());

            if ($active) {
                $this->assertParentIsActive($locked);
            } else {
                $this->assertNotInUse($locked);
            }

            $old = (bool) $locked->getAttribute('is_active');
            $locked->update(['is_active' => $active]);
            $this->auditLogs->write(
                actor: $actor,
                event: $event,
                description: ($active ? 'Activated ' : 'Deactivated ')."{$subject} {$locked->getAttribute('code')}.",
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $locked,
                subjectName: (string) ($locked->getAttribute('name') ?: $locked->getAttribute('title')),
                oldValues: ['is_active' => $old],
                newValues: ['is_active' => $active],
                actorContext: 'administrator',
            );

            return $locked->refresh();
        });
    }

    private function assertParentIsActive(Model $model): void
    {
        if ($model instanceof Program && ! $model->department()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages([
                'configuration' => 'Activate the parent department before enabling this program.',
            ]);
        }
    }

    private function assertNotInUse(Model $model): void
    {
        if ($model instanceof Department) {
            $activePrograms = $model->programs()->where('is_active', true)->count();
            $activeFaculty = $model->facultyProfiles()
                ->whereHas('user', fn ($query) => $query->where('status', 'active'))
                ->count();

            if ($activePrograms > 0 || $activeFaculty > 0) {
                throw ValidationException::withMessages([
                    'configuration' => "{$model->code} is in use by {$activePrograms} active program(s) and {$activeFaculty} active faculty member(s). Reassign or deactivate those dependencies first.",
                ]);
            }

            return;
        }

        if ($model instanceof Program) {
            $activeStudents = $model->studentProfiles()
                ->whereHas('user', fn ($query) => $query->where('status', 'active'))
                ->count();
            $researchGroups = $model->researchGroups()->count();

            if ($activeStudents > 0 || $researchGroups > 0) {
                throw ValidationException::withMessages([
                    'configuration' => "{$model->code} is in use by {$activeStudents} active student(s) and {$researchGroups} research group(s). Reassign them before disabling the program.",
                ]);
            }

            return;
        }

        if ($model instanceof DefenseRoom) {
            $upcomingSchedules = $model->schedules()
                ->whereIn('status', self::ACTIVE_DEFENSE_STATUSES)
                ->where(fn ($query) => $query->where('starts_at', '>=', now())->orWhere('ends_at', '>=', now()))
                ->count();
            $upcomingSessions = $model->sessions()
                ->whereIn('status', self::ACTIVE_DEFENSE_STATUSES)
                ->whereDate('session_date', '>=', today())
                ->count();

            if ($upcomingSchedules > 0 || $upcomingSessions > 0) {
                throw ValidationException::withMessages([
                    'configuration' => "{$model->code} has {$upcomingSchedules} upcoming defense schedule(s) and {$upcomingSessions} active session(s). Reschedule them before disabling the room.",
                ]);
            }

            return;
        }

        if ($model instanceof OfficialFormDefinition) {
            $activeRecords = $model->instances()
                ->whereNotIn('status', self::TERMINAL_FORM_STATUSES)
                ->count();

            if ($activeRecords > 0) {
                throw ValidationException::withMessages([
                    'configuration' => "{$model->code} has {$activeRecords} active record(s). Complete or cancel them before disabling the form.",
                ]);
            }
        }
    }
}
