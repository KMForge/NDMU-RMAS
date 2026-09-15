<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupAdviserChangeRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class SubmitAdviserChangeRequest
{
    public function __construct(
        private readonly SubmitOfficialFormVersion $submitForm,
        private readonly AuditLogWriter $auditLogs,
    ) {}

    /** @param array<string, mixed> $payload */
    public function handle(
        User $actor,
        OfficialFormInstance $instance,
        array $payload,
        ?UploadedFile $supportingDocument = null,
    ): ResearchGroupAdviserChangeRequest {
        if (strtoupper((string) $instance->definition?->code) !== 'RES-030') {
            throw new InvalidArgumentException('This action only accepts RES-030 adviser change requests.');
        }

        $requestedAdviserId = filter_var($payload['requested_adviser_id'] ?? null, FILTER_VALIDATE_INT);
        $reason = trim((string) ($payload['reasons'] ?? ''));
        $explanation = trim((string) ($payload['supporting_explanation'] ?? ''));
        $leaderConfirmed = filter_var($payload['group_leader_confirmed'] ?? false, FILTER_VALIDATE_BOOL);

        if (! in_array('Change of Research Adviser', $payload['personnel_type'] ?? [], true)) {
            throw new InvalidArgumentException('Select Change of Research Adviser before submitting RES-030.');
        }
        if (! $requestedAdviserId || $reason === '') {
            throw new InvalidArgumentException('A requested new adviser and reason are required.');
        }
        if (! $leaderConfirmed) {
            throw new InvalidArgumentException('The research group leader must confirm this adviser change request.');
        }

        $disk = 'local';
        $storedPath = null;
        $fileMetadata = [];
        if ($supportingDocument !== null) {
            $extension = strtolower($supportingDocument->getClientOriginalExtension());
            $storedPath = 'adviser-change-requests/'.(int) $instance->research_class_group_id.'/'.Str::uuid().($extension !== '' ? ".{$extension}" : '');
            $bytes = $supportingDocument->getContent();
            if (! Storage::disk($disk)->put($storedPath, $bytes)) {
                throw new InvalidArgumentException('The supporting document could not be stored.');
            }
            $fileMetadata = [
                'supporting_document_disk' => $disk,
                'supporting_document_path' => $storedPath,
                'supporting_document_original_name' => mb_substr($supportingDocument->getClientOriginalName(), 0, 255),
                'supporting_document_mime_type' => $supportingDocument->getMimeType(),
                'supporting_document_size' => $supportingDocument->getSize(),
                'supporting_document_sha256' => hash('sha256', $bytes),
            ];
        }

        try {
            return DB::transaction(function () use ($actor, $instance, $payload, $requestedAdviserId, $reason, $explanation, $fileMetadata): ResearchGroupAdviserChangeRequest {
                $group = ResearchClassGroup::query()->with('adviser')->lockForUpdate()->findOrFail($instance->research_class_group_id);
                if (! $group->isLeader($actor)) {
                    throw new InvalidArgumentException('Only the current research group leader may submit an adviser change request.');
                }
                if ($group->adviser_id === null) {
                    throw new InvalidArgumentException('The group has no current adviser to replace. Use the initial adviser assignment process.');
                }
                if ((int) $group->adviser_id === (int) $requestedAdviserId) {
                    throw new InvalidArgumentException('The requested adviser must be different from the current adviser.');
                }

                $requestedAdviser = User::query()->findOrFail((int) $requestedAdviserId);
                if (! $requestedAdviser->isActiveAndApproved()
                    || $requestedAdviser->email_verified_at === null
                    || ! $requestedAdviser->can('classes.serve-as-adviser')) {
                    throw new InvalidArgumentException('The requested new adviser is not active and eligible to serve as an adviser.');
                }

                $otherPending = ResearchGroupAdviserChangeRequest::query()
                    ->where('research_class_group_id', $group->id)
                    ->where('status', 'submitted')
                    ->where('official_form_instance_id', '!=', $instance->id)
                    ->lockForUpdate()
                    ->exists();
                if ($otherPending) {
                    throw new InvalidArgumentException('This research group already has a pending adviser change request.');
                }

                $this->submitForm->handle($actor, $instance, $payload, 'submitted');

                $changeRequest = ResearchGroupAdviserChangeRequest::query()->updateOrCreate(
                    ['official_form_instance_id' => $instance->id],
                    array_merge([
                        'research_class_group_id' => $group->id,
                        'previous_adviser_id' => $group->adviser_id,
                        'requested_adviser_id' => (int) $requestedAdviserId,
                        'requested_by' => $actor->id,
                        'reason' => $reason,
                        'supporting_explanation' => $explanation !== '' ? $explanation : null,
                        'status' => 'submitted',
                        'leader_confirmed_at' => now(),
                    ], $fileMetadata),
                );

                $this->auditLogs->write(
                    actor: $actor,
                    event: 'adviser.change-request.submitted',
                    description: "Submitted an adviser change request for {$group->name}.",
                    requestContext: AuditRequestContext::fromRequest(request()),
                    auditable: $changeRequest,
                    subjectName: $group->name,
                    oldValues: ['adviser_id' => $group->adviser_id, 'adviser_name' => $group->adviser?->name],
                    newValues: [
                        'requested_adviser_id' => $requestedAdviser->id,
                        'requested_adviser_name' => $requestedAdviser->name,
                        'reason' => $reason,
                        'supporting_explanation' => $explanation !== '' ? $explanation : null,
                        'leader_confirmed_at' => $changeRequest->leader_confirmed_at?->toIso8601String(),
                    ],
                    actorContext: 'student-group-leader',
                );

                return $changeRequest;
            }, 3);
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk($disk)->delete($storedPath);
            }
            throw $exception;
        }
    }
}
