<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\UserType;
use App\Models\ConsultationRecord;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseRoom;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\DocumentReview;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Models\User;
use App\Models\UserSignature;
use App\Modules\OfficialForms\Actions\ApplyOfficialFormSignature;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\DeactivateOfficialFormActor;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
use App\Modules\OfficialForms\Services\InstitutionalActorResolver;
use App\Modules\OfficialForms\Services\OfficialFormAuthorization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class OfficialFormWorkspaceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', OfficialFormInstance::class);

        $instances = $this->visibleInstances($request)
            ->sortByDesc('updated_at')
            ->values();

        $pendingInstances = $this->pendingInstances($request);

        return view('pages.official-forms.workspace-index', [
            'instances' => $instances,
            'pendingInstances' => $pendingInstances,
            'contexts' => $this->availableContexts($request),
            'definitions' => OfficialFormDefinition::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'res026UnlockedGroupIds' => Document::query()
                ->where('document_stage', DocumentStage::TitleProposal->value)
                ->where('status', DocumentStatus::ApprovedForPresentation->value)
                ->where('is_current', true)
                ->pluck('research_class_group_id'),
            'res031UnlockedGroupIds' => OfficialFormInstance::query()
                ->where('status', 'approved')
                ->whereHas('definition', fn ($query) => $query->where('code', 'RES-026'))
                ->whereHas('titlePresentation', fn ($query) => $query->where('status', 'finalized'))
                ->pluck('research_class_group_id'),
        ]);
    }

    public function show(Request $request, OfficialFormInstance $instance): View
    {
        $this->authorize('view', $instance);

        $instance->load([
            'definition', 'currentVersion.signatures', 'versions.creator', 'group.members.student', 'group.researchGroup',
            'group.leader', 'group.adviser', 'group.researchClass.officialFormActorAssignments.user',
            'researchClass.officialFormActorAssignments.user', 'actorAssignments.user', 'source',
            'titlePresentation.defense.currentSchedule.room', 'titlePresentation.defense.activePanelAssignments.user',
        ]);

        $canManageActors = Gate::forUser($request->user())->allows('assignActor', $instance);
        $allowedActorTypes = AssignOfficialFormActor::FORM_ALLOWED_ACTOR_TYPES[strtoupper($instance->definition->code)] ?? [];
        $eligibleFaculty = $canManageActors && $allowedActorTypes !== []
            ? User::query()
                ->where('user_type', UserType::Faculty)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();
        $actorOptions = collect($allowedActorTypes)->mapWithKeys(function (string $actorType) use ($eligibleFaculty, $instance): array {
            $key = strtoupper($instance->definition->code).":{$actorType}";
            $permissions = AssignOfficialFormActor::ACTOR_PERMISSION_REQUIREMENTS[$key] ?? [];

            return [$actorType => $eligibleFaculty->filter(fn (User $candidate): bool => $permissions === []
                || collect($permissions)->contains(fn (string $permission): bool => $candidate->hasPermissionTo($permission)))->values()];
        });
        $isOwningFacilitator = $instance->group?->researchClass !== null
            && (int) $instance->group->researchClass->facilitator_id === (int) $request->user()->id
            && $request->user()->can('defenses.manage');
        $titlePanelCandidates = $isOwningFacilitator && strtoupper($instance->definition->code) === 'RES-026'
            ? User::query()
                ->where('user_type', UserType::Faculty)
                ->where('status', AccountStatus::Active)
                ->whereNotNull('approved_at')
                ->whereNotNull('email_verified_at')
                ->permission('evaluations.create')
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $resolver = app(InstitutionalActorResolver::class);
        $autoResolvedActors = [
            'dean' => $resolver->dean(),
            'program_coordinator' => $resolver->programCoordinatorForGroup($instance->group) ?? $resolver->programCoordinatorForClass($instance->researchClass),
            'facilitator' => $instance->group?->researchClass?->facilitator ?? $instance->researchClass?->facilitator,
            'adviser' => $instance->group?->adviser,
        ];

        return view('pages.official-forms.workspace-show', [
            'instance' => $instance,
            'payload' => $instance->currentVersion?->payload ?? [],
            'canManageActors' => $canManageActors,
            'actorOptions' => $actorOptions,
            'autoResolvedActors' => $autoResolvedActors,
            'isOwningFacilitator' => $isOwningFacilitator,
            'titlePanelCandidates' => $titlePanelCandidates,
            'defenseRooms' => $isOwningFacilitator ? DefenseRoom::query()->where('is_active', true)->orderBy('name')->get() : collect(),
            'availableActions' => collect(['sign_chairperson', 'sign_member_1', 'sign_member_2', 'endorse', 'receive', 'approve', 'certify', 'validate', 'review', 'sign'])
                ->filter(function (string $action) use ($request, $instance): bool {
                    $transition = app(OfficialFormAuthorization::class)->transitionFor($instance, $action);

                    return $transition !== null
                        && in_array($instance->status, $transition['from'], true)
                        && Gate::forUser($request->user())->allows($action, $instance)
                        && ! $instance->currentVersion?->signatures->contains(
                            fn ($signature): bool => (int) $signature->signer_user_id === (int) $request->user()->id
                                && $signature->academic_action === $action,
                        );
                })
                ->values(),
        ]);
    }

    public function store(
        Request $request,
        OfficialFormDefinition $definition,
        CreateOfficialFormInstance $create,
    ): RedirectResponse {
        $this->rejectUnexpectedInput($request, ['group_id', 'class_id', 'context_key', 'source_kind', 'source_id', 'payload']);
        $validated = $request->validate([
            'group_id' => ['nullable', 'integer', 'exists:research_class_groups,id'],
            'class_id' => ['nullable', 'integer', 'exists:research_classes,id'],
            'context_key' => ['nullable', 'string', 'max:100', 'regex:/\A[a-z0-9_-]+\z/'],
            'source_kind' => ['nullable', Rule::in(['consultation_record', 'document_review', 'revision_request', 'res_042', 'defense_schedule'])],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'payload' => ['sometimes', 'array'],
        ]);

        [$groupId, $classId] = $this->deriveOwner($request, $definition, $validated);
        $sourceType = match ($validated['source_kind'] ?? null) {
            'consultation_record' => ConsultationRecord::class,
            'document_review' => DocumentReview::class,
            'revision_request' => RevisionRequest::class,
            'res_042' => OfficialFormInstance::class,
            'defense_schedule' => DefenseSchedule::class,
            default => null,
        };

        try {
            $instance = $create->handle(
                $request->user(),
                $definition->code,
                $groupId,
                $classId,
                $validated['context_key'] ?? 'general',
                $sourceType,
                $validated['source_id'] ?? null,
                null,
                $validated['payload'] ?? [],
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()])->withInput();
        }

        return to_route('official-forms.workspace.show', $instance)
            ->with('official_form_success', "{$definition->code} was created.");
    }

    public function storeFromSource(
        Request $request,
        OfficialFormDefinition $definition,
        string $sourceKind,
        int $source,
        CreateOfficialFormInstance $create,
    ): RedirectResponse {
        $this->rejectUnexpectedInput($request, ['context_key']);
        $sourceModel = match ($sourceKind) {
            'consultation-record' => ConsultationRecord::query()->findOrFail($source),
            'document-review' => DocumentReview::query()->with('document')->findOrFail($source),
            'revision-request' => RevisionRequest::query()->findOrFail($source),
            'res-042' => OfficialFormInstance::query()->with('definition')->findOrFail($source),
            'defense-schedule' => DefenseSchedule::query()->with('defense')->findOrFail($source),
            default => abort(404),
        };

        $groupId = match (true) {
            $sourceModel instanceof DocumentReview => $sourceModel->research_class_group_id ?? $sourceModel->document?->research_class_group_id,
            $sourceModel instanceof DefenseSchedule => $sourceModel->defense?->research_class_group_id,
            default => $sourceModel->research_class_group_id,
        };

        $existing = OfficialFormInstance::query()
            ->where('official_form_definition_id', $definition->id)
            ->where('source_type', $sourceModel::class)
            ->where('source_id', $sourceModel->getKey())
            ->where(function ($query) use ($request) {
                $query->where('initiated_by', $request->user()->id)
                    ->orWhereHas('actorAssignments', function ($aq) use ($request) {
                        $aq->where('user_id', $request->user()->id)->where('status', 'active');
                    });
            })
            ->first();

        if ($existing) {
            return to_route('official-forms.workspace.show', $existing);
        }

        try {
            $instance = $create->handle(
                $request->user(),
                $definition->code,
                (int) $groupId,
                null,
                $request->string('context_key', 'general')->toString(),
                $sourceModel::class,
                $sourceModel->getKey(),
                $request->user()->getKey(),
                [],
            );
        } catch (InvalidArgumentException $exception) {
            $fallback = OfficialFormInstance::query()
                ->where('official_form_definition_id', $definition->id)
                ->where('research_class_group_id', (int) $groupId)
                ->where(function ($query) use ($request) {
                    $query->where('initiated_by', $request->user()->id)
                        ->orWhereHas('actorAssignments', function ($aq) use ($request) {
                            $aq->where('user_id', $request->user()->id)->where('status', 'active');
                        });
                })
                ->latest('id')
                ->first();

            if ($fallback) {
                return to_route('official-forms.workspace.show', $fallback);
            }

            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return to_route('official-forms.workspace.show', $instance)
            ->with('official_form_success', "{$definition->code} was created from its authoritative source.");
    }

    public function save(
        Request $request,
        OfficialFormInstance $instance,
        SaveOfficialFormDraft $save,
    ): RedirectResponse {
        $this->authorize('updateDraft', $instance);
        $this->rejectUnexpectedInput($request, ['payload']);
        $payload = $request->validate(['payload' => ['present', 'array']])['payload'];

        try {
            $save->handle($request->user(), $instance, $payload);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()])->withInput();
        }

        return back()->with('official_form_success', 'A new immutable draft version was saved.');
    }

    public function submit(
        Request $request,
        OfficialFormInstance $instance,
        SubmitOfficialFormVersion $submit,
    ): RedirectResponse {
        $this->authorize('submit', $instance);
        $this->rejectUnexpectedInput($request, ['payload']);
        $payload = $request->validate(['payload' => ['present', 'array']])['payload'];

        try {
            $submit->handle($request->user(), $instance, $payload, 'submitted');
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()])->withInput();
        }

        return back()->with('official_form_success', 'The form was submitted for its verified next action.');
    }

    public function action(
        Request $request,
        OfficialFormInstance $instance,
        string $action,
        ApproveOfficialForm $approve,
        CertifyOfficialForm $certify,
        ApplyOfficialFormSignature $applySignature,
    ): RedirectResponse {
        abort_unless(in_array($action, ['endorse', 'receive', 'approve', 'certify', 'validate'], true), 404);
        $this->rejectUnexpectedInput($request, ['payload']);
        $this->authorize($action, $instance);

        try {
            $user = $request->user();
            $hasSignatureSpecimen = UserSignature::query()->where('user_id', $user->id)->exists();

            if ($hasSignatureSpecimen && $instance->currentVersion) {
                $applySignature->handle(
                    $user,
                    $instance->id,
                    (int) $instance->currentVersion->id,
                    $action,
                    $request
                );
            } else {
                if ($action === 'certify') {
                    $certify->handle($user, $instance);
                } else {
                    $target = match ($action) {
                        'endorse' => 'endorsed',
                        'receive', 'approve' => 'approved',
                        'validate' => 'completed',
                    };
                    $approve->handle($user, $instance, [], $target, $action);
                }
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return back()->with('official_form_success', ucfirst($action).' action recorded.');
    }

    public function signAction(
        Request $request,
        OfficialFormInstance $instance,
        string $action,
        ApplyOfficialFormSignature $applySignature,
    ): RedirectResponse {
        $this->rejectUnexpectedInput($request, ['expected_version_id']);
        $validated = $request->validate([
            'expected_version_id' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $applySignature->handle(
                $request->user(),
                $instance->id,
                (int) $validated['expected_version_id'],
                $action,
                $request
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return back()->with('official_form_success', 'Digital signature attestation recorded.');
    }

    public function assignActor(
        Request $request,
        OfficialFormInstance $instance,
        AssignOfficialFormActor $assign,
    ): RedirectResponse {
        $this->authorize('assignActor', $instance);
        $this->rejectUnexpectedInput($request, ['actor_type', 'user_id']);
        $validated = $request->validate([
            'actor_type' => ['required', 'string', 'max:50'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $assign->handle($request->user(), $instance, (int) $validated['user_id'], $validated['actor_type']);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return back()->with('official_form_success', 'The form actor assignment was saved.');
    }

    public function deactivateActor(
        Request $request,
        OfficialFormInstance $instance,
        OfficialFormActorAssignment $assignment,
        DeactivateOfficialFormActor $deactivate,
    ): RedirectResponse {
        $this->authorize('assignActor', $instance);
        $this->rejectUnexpectedInput($request, []);

        try {
            $deactivate->handle($request->user(), $instance, $assignment);
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return back()->with('official_form_success', 'The form actor assignment was deactivated.');
    }

    /** @return Collection<int, OfficialFormInstance> */
    public function pendingInstances(Request $request): Collection
    {
        $user = $request->user();
        if (! $user) {
            return collect();
        }

        $authorization = app(OfficialFormAuthorization::class);

        return $this->visibleInstances($request)
            ->filter(function (OfficialFormInstance $instance) use ($user, $authorization): bool {
                $code = strtoupper((string) ($instance->definition?->code ?? ''));

                // Facilitator pending action on submitted RES-026 (Title presentation scheduling / panel / verdict)
                if ($code === 'RES-026' && in_array($instance->status, ['submitted', 'in_review'], true)) {
                    $isFacilitator = (int) ($instance->researchClass?->facilitator_id ?? $instance->group?->researchClass?->facilitator_id ?? 0) === (int) $user->id;
                    if ($isFacilitator && $user->can('defenses.manage')) {
                        $presentation = $instance->titlePresentation;
                        if ($presentation === null || in_array($presentation->status, ['scheduled', 'panel_assigned', 'presented'], true)) {
                            return true;
                        }
                    }
                }

                return collect([
                    'sign',
                    'sign_chairperson',
                    'sign_member_1',
                    'sign_member_2',
                    'endorse',
                    'receive',
                    'approve',
                    'certify',
                    'validate',
                ])
                    ->contains(function (string $action) use ($user, $instance, $authorization): bool {
                        $transition = $authorization->transitionFor($instance, $action);

                        return $transition !== null
                            && in_array($instance->status, $transition['from'], true)
                            && Gate::forUser($user)->allows($action, $instance)
                            && ! $instance->currentVersion?->signatures->contains(
                                fn ($signature): bool => (int) $signature->signer_user_id === (int) $user->id
                                    && $signature->academic_action === $action,
                            );
                    });
            })
            ->values();
    }

    /** @return Collection<int, OfficialFormInstance> */
    private function visibleInstances(Request $request): Collection
    {
        return OfficialFormInstance::query()
            ->with([
                'definition',
                'currentVersion.signatures.signer',
                'currentVersion.creator',
                'versions.creator',
                'group.researchClass',
                'group.adviser',
                'group.leader',
                'group.researchGroup.currentProject',
                'researchClass.facilitator',
                'actorAssignments.user',
                'titlePresentation',
                'source',
                'initiatedBy',
            ])
            ->where(function ($query) use ($request): void {
                $userId = $request->user()->id;
                $query->where('initiated_by', $userId)
                    ->orWhereHas('actorAssignments', fn ($q) => $q->where('user_id', $userId)->where('status', 'active'))
                    ->orWhereHas('group', fn ($q) => $q
                        ->where('adviser_id', $userId)
                        ->orWhereHas('members', fn ($members) => $members->where('student_id', $userId))
                        ->orWhereHas('researchClass', fn ($class) => $class->where('facilitator_id', $userId)
                            ->orWhereHas('officialFormActorAssignments', fn ($actors) => $actors->where('user_id', $userId)->where('status', 'active'))))
                    ->orWhereHas('researchClass', fn ($q) => $q->where('facilitator_id', $userId)
                        ->orWhereHas('officialFormActorAssignments', fn ($actors) => $actors->where('user_id', $userId)->where('status', 'active')))
                    ->orWhereHas('titlePresentation.defense.activePanelAssignments', fn ($panel) => $panel->where('user_id', $userId));

                if ($request->user()->can('users.manage') || $request->user()->can('dashboards.dean.view') || $request->user()->hasRole('college-dean') || $request->user()->hasRole('dean')) {
                    $query->orWhereNotNull('id');
                }
            })
            ->get()
            ->filter(fn (OfficialFormInstance $instance) => Gate::forUser($request->user())->allows('view', $instance))
            ->each(function (OfficialFormInstance $instance): void {
                $authorName = $instance->currentVersion?->creator?->name
                    ?? $instance->initiatedBy?->name
                    ?? $instance->actorAssignments->first()?->user?->name
                    ?? 'Authorized Academic Actor';

                $authorRole = 'Academic Author';
                $code = strtoupper($instance->definition->code);

                if ($code === 'RES-036') {
                    $creatorId = $instance->currentVersion?->created_by ?? $instance->initiated_by;
                    if ($instance->source instanceof DefenseSchedule) {
                        $assignment = $instance->source->defense?->activePanelAssignments->firstWhere('user_id', $creatorId);
                        $authorRole = match ($assignment?->panel_position) {
                            'chairperson' => 'Chairperson',
                            'member_1' => 'Panel Member 1',
                            'member_2' => 'Panel Member 2',
                            default => 'Defense Panelist',
                        };
                    } else {
                        $authorRole = 'Defense Panelist';
                    }
                    if ($instance->currentVersion?->creator) {
                        $authorName = $instance->currentVersion->creator->name;
                    }
                } elseif ($code === 'RES-037') {
                    if ($instance->source instanceof DefenseEvaluationRound) {
                        $authorName = $instance->source->summarySigner?->name ?? $authorName;
                        $authorRole = 'Summary Signer / Chairperson';
                    }
                } elseif ($code === 'RES-040') {
                    $authorName = $instance->group?->adviser?->name ?? $authorName;
                    $authorRole = 'Thesis Adviser';
                } elseif ($code === 'RES-041') {
                    $authorName = $instance->researchClass?->facilitator?->name ?? $instance->group?->researchClass?->facilitator?->name ?? $authorName;
                    $authorRole = 'Research Instructor';
                } elseif ($code === 'RES-026') {
                    $authorRole = 'Research Group Leader';
                } elseif ($code === 'RES-031') {
                    $authorRole = 'Adviser & Researchers';
                }

                $instance->setAttribute('computed_author_name', $authorName);
                $instance->setAttribute('computed_author_role', $authorRole);
            });
    }

    /** @return array{groups: Collection, classes: Collection} */
    private function availableContexts(Request $request): array
    {
        $user = $request->user();
        $groups = ResearchClassGroup::query()
            ->where('status', 'active')
            ->where(function ($query) use ($user): void {
                $query->where('adviser_id', $user->id)
                    ->orWhereHas('members', fn ($q) => $q->where('student_id', $user->id))
                    ->orWhereHas('researchClass', fn ($q) => $q->where('facilitator_id', $user->id));
            })->with('researchClass:id,name')->orderBy('name')->get();

        $classes = ResearchClass::query()
            ->when(! $user->can('users.manage'), fn ($query) => $query
                ->where(function ($query) use ($user): void {
                    $query->where('facilitator_id', $user->id)
                        ->orWhereHas('officialFormActorAssignments', fn ($q) => $q->where('user_id', $user->id)->where('status', 'active'));
                }))
            ->orderBy('name')->get(['id', 'name', 'facilitator_id']);

        return compact('groups', 'classes');
    }

    /** @param array<string, mixed> $validated
     * @return array{0: ?int, 1: ?int}
     */
    private function deriveOwner(Request $request, OfficialFormDefinition $definition, array $validated): array
    {
        if ($request->user()->user_type === UserType::Student) {
            abort_unless($definition->ownership_scope === 'research_group', 403);
            $groupId = ResearchClassGroupMember::query()
                ->where('student_id', $request->user()->id)
                ->whereHas('group', fn ($q) => $q->where('status', 'active'))
                ->value('research_class_group_id');
            abort_if($groupId === null, 403, 'You are not assigned to an active research group.');

            return [(int) $groupId, null];
        }

        return $definition->ownership_scope === 'research_class'
            ? [null, isset($validated['class_id']) ? (int) $validated['class_id'] : null]
            : [isset($validated['group_id']) ? (int) $validated['group_id'] : null, null];
    }

    /** @param list<string> $allowed */
    private function rejectUnexpectedInput(Request $request, array $allowed): void
    {
        $unexpected = array_values(array_diff(array_keys($request->except(['_token', '_method'])), $allowed));
        if ($unexpected !== []) {
            throw ValidationException::withMessages([
                'official_form' => 'Unexpected or system-managed fields are not accepted: '.implode(', ', $unexpected).'.',
            ]);
        }
    }
}
