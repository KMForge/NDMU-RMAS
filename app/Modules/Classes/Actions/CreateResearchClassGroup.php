<?php

namespace App\Modules\Classes\Actions;

use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\Classes\Exceptions\ClassOperationException;
use App\Modules\Classes\Exceptions\DuplicateClassOperation;
use App\Modules\ResearchProgress\Actions\InitializeGroupMilestones;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateResearchClassGroup
{
    public function __construct(
        private readonly InitializeGroupMilestones $initializeMilestones,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    public function handle(
        User $facilitator,
        ResearchClass $researchClass,
        string $creationToken,
        string $name,
    ): ResearchClassGroup {
        $lock = Cache::lock("class-group:{$researchClass->getKey()}:{$creationToken}", 30);

        if (! $lock->get()) {
            throw new DuplicateClassOperation('This group creation request is already being processed.');
        }

        try {
            return DB::transaction(function () use ($facilitator, $researchClass, $creationToken, $name): ResearchClassGroup {
                $lockedClass = ResearchClass::query()->lockForUpdate()->findOrFail($researchClass->getKey());

                if ($lockedClass->facilitator_id !== $facilitator->getKey()) {
                    throw new AuthorizationException('You cannot manage groups for this class.');
                }

                $trimmedName = trim($name);

                $duplicate = ResearchClassGroup::query()
                    ->where('research_class_id', $lockedClass->getKey())
                    ->where('status', 'active')
                    ->where(function ($query) use ($creationToken, $trimmedName): void {
                        $query->where('creation_token', $creationToken)->orWhere('name', $trimmedName);
                    })
                    ->lockForUpdate()
                    ->exists();

                if ($duplicate) {
                    throw new DuplicateClassOperation('A group with this name already exists in this class.');
                }

                $group = ResearchClassGroup::query()->create([
                    'research_class_id' => $lockedClass->getKey(),
                    'creation_token' => $creationToken,
                    'name' => $trimmedName,
                    'created_by' => $facilitator->getKey(),
                    'status' => 'active',
                ]);

                $this->initializeMilestones->execute($group);

                $this->auditLogs->write(
                    actor: $facilitator,
                    event: 'research-group.created',
                    description: 'A research class group was created.',
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $group,
                    subjectName: $group->name,
                    newValues: ['research_class_id' => $lockedClass->getKey(), 'status' => 'active'],
                    actorContext: 'research-facilitator',
                );

                return $group;
            }, 3);
        } catch (QueryException $exception) {
            report($exception);

            throw new ClassOperationException('The class group could not be created. Please try again.');
        } finally {
            $lock->release();
        }
    }
}
