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

final class ManageAcademicConfiguration
{
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
            $old = (bool) $model->getAttribute('is_active');
            $model->update(['is_active' => $active]);
            $this->auditLogs->write(
                actor: $actor,
                event: $event,
                description: ($active ? 'Activated ' : 'Deactivated ')."{$subject} {$model->getAttribute('code')}.",
                requestContext: AuditRequestContext::fromRequest(request()),
                auditable: $model,
                subjectName: (string) ($model->getAttribute('name') ?: $model->getAttribute('title')),
                oldValues: ['is_active' => $old],
                newValues: ['is_active' => $active],
                actorContext: 'administrator',
            );

            return $model;
        });
    }
}
