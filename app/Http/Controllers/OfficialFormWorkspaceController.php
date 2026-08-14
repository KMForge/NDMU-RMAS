<?php

namespace App\Http\Controllers;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\ConsultationRecord;
use App\Models\DocumentReview;
use App\Models\OfficialFormActorAssignment;
use App\Models\OfficialFormDefinition;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClass;
use App\Models\ResearchClassGroup;
use App\Models\ResearchClassGroupMember;
use App\Models\RevisionRequest;
use App\Modules\OfficialForms\Actions\ApproveOfficialForm;
use App\Modules\OfficialForms\Actions\AssignOfficialFormActor;
use App\Modules\OfficialForms\Actions\CertifyOfficialForm;
use App\Modules\OfficialForms\Actions\CreateOfficialFormInstance;
use App\Modules\OfficialForms\Actions\DeactivateOfficialFormActor;
use App\Modules\OfficialForms\Actions\SaveOfficialFormDraft;
use App\Modules\OfficialForms\Actions\SubmitOfficialFormVersion;
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

        return view('pages.official-forms.workspace-index', [
            'instances' => $instances,
            'contexts' => $this->availableContexts($request),
            'definitions' => OfficialFormDefinition::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(Request $request, OfficialFormInstance $instance): View
    {
        $this->authorize('view', $instance);

        $instance->load([
            'definition', 'currentVersion', 'versions.creator', 'group.members.student', 'group.researchGroup',
            'group.leader', 'group.adviser', 'group.researchClass.officialFormActorAssignments.user',
            'researchClass.officialFormActorAssignments.user', 'actorAssignments.user', 'source',
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

        return view('pages.official-forms.workspace-show', [
            'instance' => $instance,
            'payload' => $instance->currentVersion?->payload ?? [],
            'canManageActors' => $canManageActors,
            'actorOptions' => $actorOptions,
            'availableActions' => collect(['endorse', 'receive', 'approve', 'certify', 'validate'])
                ->filter(function (string $action) use ($request, $instance): bool {
                    $transition = app(OfficialFormAuthorization::class)->transitionFor($instance, $action);

                    return $transition !== null
                        && in_array($instance->status, $transition['from'], true)
                        && Gate::forUser($request->user())->allows($action, $instance);
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
            'source_kind' => ['nullable', Rule::in(['consultation_record', 'document_review', 'revision_request', 'res_042'])],
            'source_id' => ['nullable', 'integer', 'min:1'],
            'payload' => ['sometimes', 'array'],
        ]);

        [$groupId, $classId] = $this->deriveOwner($request, $definition, $validated);
        $sourceType = match ($validated['source_kind'] ?? null) {
            'consultation_record' => ConsultationRecord::class,
            'document_review' => DocumentReview::class,
            'revision_request' => RevisionRequest::class,
            'res_042' => OfficialFormInstance::class,
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
            default => abort(404),
        };

        $groupId = match (true) {
            $sourceModel instanceof DocumentReview => $sourceModel->research_class_group_id ?? $sourceModel->document?->research_class_group_id,
            default => $sourceModel->research_class_group_id,
        };

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
    ): RedirectResponse {
        abort_unless(in_array($action, ['endorse', 'receive', 'approve', 'certify', 'validate'], true), 404);
        $this->rejectUnexpectedInput($request, []);
        $this->authorize($action, $instance);

        try {
            if ($action === 'certify') {
                $certify->handle($request->user(), $instance);
            } else {
                $target = match ($action) {
                    'endorse' => 'endorsed',
                    'receive', 'approve' => 'approved',
                    'validate' => 'completed',
                };
                $approve->handle($request->user(), $instance, [], $target, $action);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['official_form' => $exception->getMessage()]);
        }

        return back()->with('official_form_success', ucfirst($action).' action recorded.');
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
    private function visibleInstances(Request $request): Collection
    {
        return OfficialFormInstance::query()
            ->with(['definition', 'currentVersion', 'group.researchClass', 'researchClass'])
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
                        ->orWhereHas('officialFormActorAssignments', fn ($actors) => $actors->where('user_id', $userId)->where('status', 'active')));

                if ($request->user()->can('users.manage')) {
                    $query->orWhereNotNull('id');
                }
            })
            ->get()
            ->filter(fn (OfficialFormInstance $instance) => Gate::forUser($request->user())->allows('view', $instance));
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
