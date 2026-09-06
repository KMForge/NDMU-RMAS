<?php

namespace App\Modules\OfficialForms\Actions;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\AuditLog;
use App\Models\ConsultationRecord;
use App\Models\Defense;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\OfficialFormVersion;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Modules\Evaluations\Services\Res036Rubric;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CreateOfficialFormInstance
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly OfficialFormPayloadValidator $payloadValidator = new OfficialFormPayloadValidator,
        private readonly Res036Rubric $res036Rubric = new Res036Rubric,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    /** @var array<string, list<string>> */
    public const FORM_ALLOWED_SOURCE_TYPES = [
        'RES-026' => [Document::class],
        'RES-031' => [ConsultationRecord::class],
        'RES-036' => [DefenseSchedule::class],
        'RES-037' => [DefenseEvaluationRound::class, DefenseSchedule::class],
        'RES-039' => [DocumentReview::class, RevisionRequest::class],
        'RES-043A' => [OfficialFormInstance::class],
        'RES-043B' => [OfficialFormInstance::class],
    ];

    /** @var list<string> */
    private const FORMS_REQUIRING_SOURCE = [
        'RES-026',
        'RES-036',
        'RES-037',
        'RES-039',
        'RES-043A',
        'RES-043B',
    ];

    public function handle(
        User $initiator,
        string $formCode,
        ?int $groupId = null,
        ?int $classId = null,
        string $contextKey = 'general',
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?int $actorUserId = null,
        array $payload = [],
    ): OfficialFormInstance {
        $formCodeUpper = strtoupper($formCode);

        $validatedPayload = $this->payloadValidator->validate($formCodeUpper, $payload);

        $definition = OfficialFormDefinition::query()
            ->where('code', $formCodeUpper)
            ->where('is_active', true)
            ->firstOrFail();

        $sourceSnapshot = null;

        if ($formCodeUpper === 'RES-036' && $sourceType === DefenseSchedule::class) {
            /** @var DefenseSchedule|null $defenseSchedule */
            $defenseSchedule = DefenseSchedule::query()->with([
                'defense.group.members.student',
                'defense.group.researchGroup.currentProject',
                'room',
            ])->find($sourceId);
            if (! $defenseSchedule) {
                throw new InvalidArgumentException('Target DefenseSchedule source does not exist.');
            }

            if (! $this->authorization->canInitiateDefenseEvaluation($initiator, $defenseSchedule)) {
                throw new InvalidArgumentException("User #{$initiator->id} is not authorized to initiate RES-036 for defense schedule #{$sourceId}.");
            }

            $groupId = (int) $defenseSchedule->defense->research_class_group_id;
            $group = $defenseSchedule->defense->group;
            $researchTitle = $group?->researchGroup?->currentProject?->title ?? $group?->name ?? 'Untitled Research';

            $presenters = [];
            if ($group && $group->members) {
                $idx = 1;
                foreach ($group->members as $member) {
                    $presenters[$idx++] = [
                        'student_id' => $member->student_id,
                        'name' => $member->student?->name ?? '',
                        'scores' => [],
                    ];
                }
            }
            $sourceSnapshot = [
                'defense_type' => $defenseSchedule->defense->defense_type,
                'starts_at' => $defenseSchedule->starts_at?->toIso8601String(),
                'ends_at' => $defenseSchedule->ends_at?->toIso8601String(),
                'room_code' => $defenseSchedule->room?->code,
                'room_name' => $defenseSchedule->room?->name,
                'location_notes' => $defenseSchedule->room?->location_notes,
                'research_title' => $researchTitle,
                'group_name' => $group?->name ?? 'Group #'.$group?->id,
                'program_code' => $this->res036Rubric->resolveProgramCode($group),
                'presenters' => array_values($presenters),
            ];

            $defaultPayload = [
                'res_036_defense_type' => in_array($defenseSchedule->defense->defense_type, ['proposal_defense', 'title_proposal', 'proposal']) ? 'proposal' : 'final',
                'res_036_date' => $defenseSchedule->starts_at?->format('Y-m-d'),
                'res_036_time' => $defenseSchedule->starts_at?->format('H:i'),
                'res_036_venue' => $defenseSchedule->room?->name ?? $defenseSchedule->room?->code,
                'res_036_research_title' => $researchTitle,
                'res_036_presenters' => $presenters,
                'res_036_panelist_printed_name' => $initiator->name,
                'res_036_signed_at' => now()->format('Y-m-d'),
            ];

            $validatedPayload = array_merge($defaultPayload, $validatedPayload);
        }

        if ($formCodeUpper === 'RES-037' && $sourceType === DefenseSchedule::class && $sourceId !== null) {
            /** @var DefenseSchedule|null $defenseSchedule */
            $defenseSchedule = DefenseSchedule::query()->find($sourceId);
            $round = $defenseSchedule ? DefenseEvaluationRound::query()->where('defense_schedule_id', $defenseSchedule->id)->latest('id')->first() : null;
            if ($round) {
                $sourceType = DefenseEvaluationRound::class;
                $sourceId = $round->id;
                $groupId = (int) $round->research_class_group_id;
            }
        }

        $group = $groupId !== null ? ResearchClassGroup::query()->find($groupId) : null;
        $class = $classId !== null ? ResearchClass::query()->find($classId) : null;

        if ($formCodeUpper === 'RES-048') {
            if ($group === null || ! $group->isActive()) {
                throw new InvalidArgumentException('RES-048 requires an active research class group.');
            }

            $targetActorId = $initiator->id;
            $sourceSnapshot = $this->buildPeerEvaluationSnapshot($initiator, $group);
        }

        if ($formCodeUpper === 'RES-026' && $group !== null && $sourceType === null) {
            $approvedDocument = Document::query()
                ->where('research_class_group_id', $group->id)
                ->where('document_stage', DocumentStage::TitleProposal->value)
                ->where('status', DocumentStatus::ApprovedForPresentation->value)
                ->where('is_current', true)
                ->latest('version_number')
                ->first();

            if ($approvedDocument === null) {
                throw new InvalidArgumentException('Your Title Proposal document must first be approved for Title Presentation.');
            }

            $sourceType = Document::class;
            $sourceId = $approvedDocument->id;
        }

        $this->validateOwnershipScope($definition, $groupId, $classId);

        if (($sourceType === null) !== ($sourceId === null)) {
            throw new InvalidArgumentException('Source type and source ID must be provided together.');
        }

        if (in_array($formCodeUpper, self::FORMS_REQUIRING_SOURCE, true) && $sourceType === null) {
            throw new InvalidArgumentException("Form {$formCodeUpper} requires its configured authoritative source.");
        }

        if ($sourceType !== null) {
            $allowedSources = self::FORM_ALLOWED_SOURCE_TYPES[$formCodeUpper] ?? null;
            if ($allowedSources === null || ! in_array($sourceType, $allowedSources, true)) {
                throw new InvalidArgumentException("Source type [{$sourceType}] is not permitted for form {$formCodeUpper}.");
            }
        }

        $targetActorId = $formCodeUpper === 'RES-048'
            ? $initiator->id
            : ($actorUserId ?? $initiator->id);

        // Authoritative Source Enforcements per form
        if (in_array($formCodeUpper, ['RES-043A', 'RES-043B'], true)) {
            $targetActorId = $this->validateValidationRequestSourceAndValidator($groupId, $sourceType, $sourceId, $targetActorId);
        }

        if ($formCodeUpper === 'RES-031') {
            $this->validateRes031Prerequisites($group);
        }

        if ($formCodeUpper !== 'RES-036' && ! $this->authorization->canInitiate($initiator, $definition, $group, $class)) {
            throw new InvalidArgumentException("User #{$initiator->id} is not authorized to initiate form {$definition->code}.");
        }

        return DB::transaction(function () use ($definition, $initiator, $groupId, $classId, $contextKey, $sourceType, $sourceId, $targetActorId, $validatedPayload, $sourceSnapshot) {
            // Lock owner record for update to prevent concurrent duplicate creation
            if ($groupId !== null) {
                ResearchClassGroup::query()->lockForUpdate()->find($groupId);
            } elseif ($classId !== null) {
                ResearchClass::query()->lockForUpdate()->find($classId);
            }

            $this->validateSourceLinkage($initiator, $groupId, $sourceType, $sourceId);
            $this->validateCardinality($definition, $groupId, $classId, $contextKey, $targetActorId, $sourceId);

            $instance = OfficialFormInstance::query()->create([
                'official_form_definition_id' => $definition->id,
                'research_class_group_id' => $groupId,
                'research_class_id' => $classId,
                'context_key' => $contextKey,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'initiated_by' => $initiator->id,
                'status' => 'draft',
            ]);

            $version = OfficialFormVersion::query()->create([
                'official_form_instance_id' => $instance->id,
                'version_number' => 1,
                'payload' => $validatedPayload,
                'source_snapshot' => $sourceSnapshot,
                'created_by' => $initiator->id,
                'is_current' => true,
            ]);

            $instance->update(['current_version_id' => $version->id]);

            // For per_actor cardinality forms, mirror the verified target actor assignment atomically
            if ($definition->cardinality === 'per_actor') {
                $actorType = AssignOfficialFormActor::FORM_ALLOWED_ACTOR_TYPES[strtoupper($definition->code)][0] ?? 'consultant';
                OfficialFormActorAssignment::query()->updateOrCreate(
                    [
                        'official_form_instance_id' => $instance->id,
                        'actor_type' => $actorType,
                        'user_id' => $targetActorId,
                    ],
                    [
                        'assigned_by' => $initiator->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                    ]
                );
            }

            AuditLog::query()->create([
                'user_id' => $initiator->id,
                'actor_name' => $initiator->name,
                'actor_email' => $initiator->email,
                'event' => strtoupper($definition->code) === 'RES-026' ? 'RES026_CREATED' : 'official_form.created',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Created official form instance {$definition->code} (v1).",
            ]);

            return $instance->load(['definition', 'currentVersion', 'actorAssignments']);
        });
    }

    private function validateOwnershipScope(OfficialFormDefinition $definition, ?int $groupId, ?int $classId): void
    {
        if ($definition->ownership_scope === 'research_group') {
            if ($groupId === null) {
                throw new InvalidArgumentException("Form {$definition->code} requires a research_class_group_id.");
            }
            if ($classId !== null) {
                throw new InvalidArgumentException("Group-owned form {$definition->code} must not specify a research_class_id.");
            }
        } elseif ($definition->ownership_scope === 'research_class') {
            if ($classId === null) {
                throw new InvalidArgumentException("Form {$definition->code} requires a research_class_id.");
            }
            if ($groupId !== null) {
                throw new InvalidArgumentException("Class-owned form {$definition->code} must not specify a research_class_group_id.");
            }
        }
    }

    private function validateRes031Prerequisites(?ResearchClassGroup $group): void
    {
        if ($group === null || ! $group->isActive()) {
            throw new InvalidArgumentException('RES-031 requires an active research class group.');
        }

        $hasFinalizedTitleApproval = OfficialFormInstance::query()
            ->where('research_class_group_id', $group->id)
            ->where('status', 'approved')
            ->whereHas('definition', fn ($query) => $query->where('code', 'RES-026'))
            ->whereHas('titlePresentation', fn ($query) => $query->where('status', 'finalized'))
            ->exists();

        if (! $hasFinalizedTitleApproval) {
            throw new InvalidArgumentException('RES-031 becomes available after the Title Presentation is finalized and RES-026 is approved.');
        }
    }

    private function validateValidationRequestSourceAndValidator(?int $groupId, ?string $sourceType, ?int $sourceId, int $targetActorId): int
    {
        if ($sourceType !== OfficialFormInstance::class || $sourceId === null) {
            throw new InvalidArgumentException('RES-043A/B validation rating requires an authoritative RES-042 validation request source.');
        }

        $sourceForm = OfficialFormInstance::query()->find($sourceId);
        if (! $sourceForm || strtoupper($sourceForm->definition->code) !== 'RES-042') {
            throw new InvalidArgumentException('Source form instance must be an official RES-042 validation request.');
        }

        if ($groupId !== null && (int) $sourceForm->research_class_group_id !== (int) $groupId) {
            throw new InvalidArgumentException('Source RES-042 validation request does not belong to the specified research group.');
        }

        // Require pre-existing instrument_validator actor assignment on the source RES-042 request instance
        $isAssigned = $sourceForm->actorAssignments()
            ->where('user_id', $targetActorId)
            ->where('actor_type', 'instrument_validator')
            ->where('status', 'active')
            ->exists();

        if (! $isAssigned) {
            throw new InvalidArgumentException("Target user #{$targetActorId} is not an assigned instrument validator for the source RES-042 validation request.");
        }

        return $targetActorId;
    }

    /** @return array{evaluator_user_id: int, roster: list<array{user_id: int, name: string, role: string}>, captured_at: string} */
    private function buildPeerEvaluationSnapshot(User $initiator, ResearchClassGroup $group): array
    {
        $members = ResearchClassGroupMember::query()
            ->where('research_class_group_id', $group->id)
            ->with('student:id,name')
            ->orderBy('student_id')
            ->get();

        if (! $members->contains(fn (ResearchClassGroupMember $member): bool => (int) $member->student_id === (int) $initiator->id)) {
            throw new InvalidArgumentException('Only a current member of the research group may create RES-048.');
        }

        if ($members->isEmpty() || $members->count() > 4) {
            throw new InvalidArgumentException('RES-048 requires a frozen research group roster of one to four students.');
        }

        $roster = $members
            ->sortBy(fn (ResearchClassGroupMember $member): int => (int) $member->student_id === (int) $initiator->id ? 0 : 1)
            ->values()
            ->map(fn (ResearchClassGroupMember $member): array => [
                'user_id' => (int) $member->student_id,
                'name' => (string) $member->student?->name,
                'role' => (int) $member->student_id === (int) $initiator->id ? 'self' : 'peer',
            ])
            ->all();

        return [
            'evaluator_user_id' => (int) $initiator->id,
            'roster' => $roster,
            'captured_at' => now()->toIso8601String(),
        ];
    }

    private function validateEditorAssignment(?int $groupId, int $targetActorId, string $requiredActorType): void
    {
        if ($groupId === null) {
            return;
        }

        $group = ResearchClassGroup::query()->find($groupId);
        if (! $group) {
            throw new InvalidArgumentException('Target research group does not exist.');
        }

        $hasAssignment = OfficialFormInstance::query()
            ->where('research_class_group_id', $groupId)
            ->whereHas('actorAssignments', fn ($q) => $q->where('user_id', $targetActorId)->where('actor_type', $requiredActorType)->where('status', 'active'))
            ->exists();

        if (! $hasAssignment) {
            throw new InvalidArgumentException("Target user #{$targetActorId} does not have an active {$requiredActorType} assignment for this research group.");
        }
    }

    private function validateSourceLinkage(User $initiator, ?int $groupId, ?string $sourceType, ?int $sourceId): void
    {
        if ($sourceType === null || $sourceId === null) {
            return;
        }

        if ($sourceType === ConsultationRecord::class) {
            $record = ConsultationRecord::query()->find($sourceId);
            if (! $record || $record->consulted_at === null || $record->is_superseded || ($groupId !== null && (int) $record->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('Source ConsultationRecord does not belong to the specified group.');
            }
        } elseif ($sourceType === Document::class) {
            $document = Document::query()->lockForUpdate()->find($sourceId);
            if (! $document
                || $document->document_stage !== DocumentStage::TitleProposal
                || $document->status !== DocumentStatus::ApprovedForPresentation
                || ! $document->is_current
                || ($groupId !== null && (int) $document->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('RES-026 requires the current approved Title Proposal document for this research group.');
            }
        } elseif ($sourceType === DocumentReview::class) {
            /** @var DocumentReview|null $review */
            $review = DocumentReview::query()->with('document')->find($sourceId);
            $reviewGroupId = $review?->research_class_group_id ?? $review?->document?->research_class_group_id;
            if (! $review || $review->reviewed_at === null || $review->is_superseded || ($groupId !== null && (int) $reviewGroupId !== (int) $groupId)) {
                throw new InvalidArgumentException('Source DocumentReview does not belong to the specified group.');
            }
        } elseif ($sourceType === RevisionRequest::class) {
            $request = RevisionRequest::query()->find($sourceId);
            if (! $request || $request->invalidated_at !== null || $request->status?->value === 'cancelled' || ($groupId !== null && (int) $request->research_class_group_id !== (int) $groupId)) {
                throw new InvalidArgumentException('Source RevisionRequest does not belong to the specified group.');
            }
        } elseif ($sourceType === DefenseSchedule::class) {
            /** @var DefenseSchedule|null $schedule */
            $schedule = DefenseSchedule::query()->lockForUpdate()->find($sourceId);
            if (! $schedule || $schedule->status !== 'current') {
                throw new InvalidArgumentException('Source DefenseSchedule is invalid or superseded.');
            }

            /** @var Defense|null $defense */
            $defense = Defense::query()->lockForUpdate()->find($schedule->defense_id);
            if (! $defense || $defense->status !== 'scheduled' || (int) $defense->current_schedule_id !== (int) $schedule->id) {
                throw new InvalidArgumentException('Source Defense is invalid or not scheduled.');
            }

            if ($groupId !== null && (int) $defense->research_class_group_id !== (int) $groupId) {
                throw new InvalidArgumentException('Source DefenseSchedule does not belong to the specified group.');
            }

            $isPanelist = DefensePanelAssignment::where('defense_id', $defense->id)
                ->where('user_id', $initiator->id)
                ->whereNull('ended_at')
                ->exists();

            if (! $isPanelist) {
                throw new InvalidArgumentException("User #{$initiator->id} is not an active Defense Panelist for this defense.");
            }

            if ($initiator->user_type !== UserType::Faculty
                || $initiator->status !== AccountStatus::Active
                || $initiator->approved_at === null
                || $initiator->email_verified_at === null
                || ! $initiator->hasPermissionTo('forms.res-036.evaluate')) {
                throw new InvalidArgumentException("User #{$initiator->id} lacks required faculty account state or permission to evaluate RES-036.");
            }
        }
    }

    private function validateCardinality(
        OfficialFormDefinition $definition,
        ?int $groupId,
        ?int $classId,
        string $contextKey,
        int $targetActorId,
        ?int $sourceId
    ): void {
        $query = OfficialFormInstance::query()
            ->where('official_form_definition_id', $definition->id);

        if ($definition->cardinality === 'single_per_group' && $groupId !== null) {
            $exists = (clone $query)->where('research_class_group_id', $groupId)->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this research group.");
            }
        } elseif ($definition->cardinality === 'single_per_context' && $groupId !== null) {
            $exists = (clone $query)
                ->where('research_class_group_id', $groupId)
                ->where('context_key', $contextKey)
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this group context ({$contextKey}).");
            }
        } elseif ($definition->cardinality === 'per_actor' && $groupId !== null) {
            $exists = (clone $query)
                ->where('research_class_group_id', $groupId)
                ->where('context_key', $contextKey)
                ->whereHas('actorAssignments', function ($aq) use ($targetActorId) {
                    $aq->where('user_id', $targetActorId)->where('status', 'active');
                })
                ->when($sourceId !== null, fn ($q) => $q->where('source_id', $sourceId))
                ->exists();
            if ($exists) {
                throw new InvalidArgumentException("Form {$definition->code} already exists for this actor user in context ({$contextKey}).");
            }
        }
    }
}
