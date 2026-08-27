<?php

namespace App\Modules\OfficialForms\Actions;

use App\Enums\AccountStatus;
use App\Models\AuditLog;
use App\Models\DefensePanelAssignment;
use App\Models\DefenseSchedule;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormInstance;
use App\Models\User;
use App\Modules\Notifications\Services\WorkflowNotificationDispatcher;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssignOfficialFormActor
{
    public function __construct(
        private readonly OfficialFormAuthorization $authorization = new OfficialFormAuthorization,
        private readonly WorkflowNotificationDispatcher $notifications = new WorkflowNotificationDispatcher,
    ) {}

    /** @var array<string, list<string>> */
    public const FORM_ALLOWED_ACTOR_TYPES = [
        'RES-027' => ['adviser'],
        'RES-028' => ['panelist'],
        'RES-029' => ['language_editor'],
        'RES-030' => ['adviser', 'panelist', 'language_editor'],
        'RES-032' => ['consultant', 'language_editor', 'technical_editor', 'instrument_validator', 'panelist'],
        'RES-034' => ['adviser', 'panelist'],
        'RES-035' => ['adviser', 'panelist'],
        'RES-036' => ['panelist'],
        'RES-037' => ['panelist'],
        'RES-038' => ['adviser'],
        'RES-040' => ['research_instructor'],
        'RES-041' => ['research_instructor', 'program_coordinator'],
        'RES-042' => ['instrument_validator'],
        'RES-043A' => ['instrument_validator'],
        'RES-043B' => ['instrument_validator'],
        'RES-044' => ['adviser', 'panelist'],
        'RES-045' => ['language_editor'],
        'RES-046' => ['technical_editor'],
        'RES-047' => ['dean', 'program_coordinator'],
    ];

    /** @var array<string, list<string>> */
    public const ACTOR_PERMISSION_REQUIREMENTS = [
        'RES-027:adviser' => ['forms.res-027.respond'],
        'RES-028:panelist' => ['forms.res-028.respond'],
        'RES-029:language_editor' => ['forms.res-029.respond'],
        'RES-040:research_instructor' => ['forms.res-040.receive'],
        'RES-041:research_instructor' => ['forms.res-041.fill', 'forms.res-041.endorse'],
        'RES-041:program_coordinator' => ['forms.res-041.receive'],
        'RES-042:instrument_validator' => ['forms.res-043a.validate', 'forms.res-043b.validate'],
        'RES-043A:instrument_validator' => ['forms.res-043a.validate'],
        'RES-043B:instrument_validator' => ['forms.res-043b.validate'],
        'RES-045:language_editor' => ['forms.res-045.certify'],
        'RES-046:technical_editor' => ['forms.res-046.certify'],
        'RES-047:dean' => ['forms.res-047.approve'],
    ];

    public function handle(
        User $assigner,
        OfficialFormInstance $instance,
        int $userId,
        string $actorType
    ): OfficialFormActorAssignment {
        if (! $this->authorization->canAssignActor($assigner, $instance)) {
            throw new InvalidArgumentException("User #{$assigner->id} is not authorized to assign actors for form instance #{$instance->id}.");
        }

        $formCode = strtoupper($instance->definition->code);

        if (! isset(self::FORM_ALLOWED_ACTOR_TYPES[$formCode])) {
            throw new InvalidArgumentException("Actor assignment is not configured for form {$formCode}.");
        }

        $allowedTypes = self::FORM_ALLOWED_ACTOR_TYPES[$formCode];

        if (! in_array($actorType, $allowedTypes, true)) {
            throw new InvalidArgumentException("Actor type [{$actorType}] is not valid for form {$formCode}.");
        }

        $user = User::query()->findOrFail($userId);

        $userTypeVal = is_object($user->user_type) ? ($user->user_type->value ?? (string) $user->user_type) : (string) $user->user_type;
        if (in_array($actorType, ['adviser', 'panelist', 'language_editor', 'technical_editor', 'instrument_validator', 'research_instructor', 'program_coordinator', 'dean'], true)) {
            if ($userTypeVal !== 'faculty' || $user->status !== AccountStatus::Active || $user->approved_at === null) {
                throw new InvalidArgumentException("Specialist actor type [{$actorType}] requires a faculty user.");
            }
        }

        $requiredPermissions = self::ACTOR_PERMISSION_REQUIREMENTS["{$formCode}:{$actorType}"] ?? [];
        if ($requiredPermissions !== [] && ! collect($requiredPermissions)->contains(
            fn (string $permission): bool => $user->hasPermissionTo($permission)
        )) {
            throw new InvalidArgumentException("User #{$user->id} lacks the required permission for actor type [{$actorType}] on {$formCode}.");
        }

        if ($formCode === 'RES-036' && $instance->source_type === DefenseSchedule::class) {
            $schedule = DefenseSchedule::query()->find($instance->source_id);
            $isPanelist = $schedule && DefensePanelAssignment::where('defense_id', $schedule->defense_id)
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->exists();
            if (! $isPanelist) {
                throw new InvalidArgumentException("User #{$user->id} is not an active Defense Panelist for this defense schedule.");
            }
        }

        return DB::transaction(function () use ($assigner, $instance, $user, $actorType) {
            $assignment = OfficialFormActorAssignment::query()->updateOrCreate(
                [
                    'official_form_instance_id' => $instance->id,
                    'actor_type' => $actorType,
                    'user_id' => $user->id,
                ],
                [
                    'assigned_by' => $assigner->id,
                    'assigned_at' => now(),
                    'status' => 'active',
                ]
            );

            AuditLog::query()->create([
                'user_id' => $assigner->id,
                'actor_name' => $assigner->name,
                'actor_email' => $assigner->email,
                'event' => 'official_form.actor_assigned',
                'auditable_type' => OfficialFormInstance::class,
                'auditable_id' => $instance->id,
                'description' => "Assigned {$user->name} as {$actorType} on form instance #{$instance->id}.",
            ]);

            $instance->loadMissing(['definition', 'group']);
            $formCode = strtoupper($instance->definition->code);

            $this->notifications->send(
                recipient: $user,
                eventKey: 'official-form.action-required',
                title: "{$formCode} requires your action",
                message: 'You were assigned as '.str($actorType)->headline()->lower()." for {$instance->definition->title}.",
                category: 'form',
                routeName: 'official-forms.workspace.show',
                routeParameters: ['instance' => $instance->getKey()],
                sourceType: OfficialFormActorAssignment::class,
                sourceId: $assignment->getKey(),
                actor: $assigner,
                contextLabel: $instance->group?->name,
                actingAs: str($actorType)->headline()->toString(),
                occurrence: $assignment->status,
            );

            return $assignment;
        });
    }
}
