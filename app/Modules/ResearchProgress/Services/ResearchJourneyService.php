<?php

namespace App\Modules\ResearchProgress\Services;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\ResearchMilestoneStatus;
use App\Models\Defense;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\User;
use App\Modules\OfficialForms\Services\OfficialResearchWorkflowRegistry;
use Illuminate\Support\Collection;

class ResearchJourneyService
{
    /** @var list<int> */
    private const BSIT_AUTOMATIC_STAGE_NUMBERS = [5, 7, 8, 9, 13];

    /**
     * @return array<string, mixed>
     */
    public function getJourneyForGroup(ResearchClassGroup $group, ?User $user = null): array
    {
        $group->loadMissing([
            'members.student.studentProfile.program', 'adviser', 'researchClass', 'defenses',
        ]);

        $automaticStageNumbers = $this->automaticStageNumbersFor($group);

        $instanceRecords = OfficialFormInstance::query()
            ->where(function ($q) use ($group) {
                $q->where('research_class_group_id', $group->id);
                if ($group->research_class_id) {
                    $q->orWhere(function ($cq) use ($group) {
                        $cq->where('research_class_id', $group->research_class_id)
                            ->whereNull('research_class_group_id');
                    });
                }
            })
            ->with(['definition', 'actorAssignments.user', 'titlePresentation.defense.currentSchedule.room', 'titlePresentation.defense.activePanelAssignments.user'])
            ->get();
        $instanceGroups = $instanceRecords
            ->groupBy(fn (OfficialFormInstance $instance): string => strtolower($instance->definition->code));
        $instances = $instanceGroups
            ->map(fn (Collection $forms): OfficialFormInstance => $forms->sortByDesc('id')->first());

        $milestones = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->id)
            ->with('definition:id,code')
            ->get()
            ->filter(fn (ResearchGroupMilestone $milestone): bool => $milestone->definition !== null)
            ->keyBy(fn (ResearchGroupMilestone $milestone): string => $milestone->definition->code);

        $stages = [];
        $completedStageCount = 0;
        $requiredStageCount = 0;
        $currentStageNumber = 1;
        $currentStageName = 'Research Title Presentation';
        $waitingOn = null;
        $nextAction = null;
        $blockers = [];
        $completedReqs = [];
        $pendingReqs = [];
        $hasCurrentStage = false;
        $previousStagesCompleted = true;
        $furthestReachedStage = $this->furthestReachedStage($group, $instanceRecords);

        $stageCount = count(config('research-progress.milestones', []));

        for ($stageNum = 1; $stageNum <= $stageCount; $stageNum++) {
            $stageDetails = $this->evaluateStage($stageNum, $group, $instances, $instanceGroups, $milestones);
            $stageDetails['is_optional'] = (bool) ($this->getStageConfig($stageNum)['optional'] ?? false);
            $stageDetails['is_not_applicable'] = $milestones->get($stageDetails['code'])?->status === ResearchMilestoneStatus::NotApplicable;
            $stageDetails['is_auto_completed'] = in_array($stageNum, $automaticStageNumbers, true);
            $stageDetails['is_inferred_complete'] = ! $stageDetails['is_optional']
                && ! $stageDetails['is_not_applicable']
                && $stageNum < $furthestReachedStage;
            $stageDetails['is_completed'] = $stageDetails['is_auto_completed']
                || $stageDetails['is_inferred_complete']
                || ($previousStagesCompleted && $stageDetails['is_completed']);

            if ($stageDetails['is_not_applicable']) {
                $stageDetails['is_completed'] = false;
                $stageDetails['waiting_on'] = null;
                $stageDetails['next_action'] = null;
                $stageDetails['blockers'] = [];
                $stageDetails['pending_requirements'] = [];
                $stageDetails['completed_requirements'] = [
                    $stageDetails['name'].' is marked not applicable for this research group.',
                ];
            } elseif ($stageDetails['is_auto_completed'] || $stageDetails['is_inferred_complete']) {
                $stageDetails['waiting_on'] = null;
                $stageDetails['next_action'] = null;
                $stageDetails['blockers'] = [];
                $stageDetails['pending_requirements'] = [];
                $stageDetails['completed_requirements'] = [
                    $stageDetails['is_auto_completed']
                        ? $stageDetails['name'].' is automatically completed for the BSIT curriculum.'
                        : $stageDetails['name'].' is complete because the group has reached a later verified workflow stage.',
                ];
            }

            $stages[$stageNum] = $stageDetails;

            // Optional stages remain visible and recordable, but they neither count
            // toward required progress nor block the next required lifecycle stage.
            if ($stageDetails['is_optional'] || $stageDetails['is_not_applicable']) {
                continue;
            }

            $requiredStageCount++;

            if ($stageDetails['is_completed']) {
                $completedStageCount++;
                $completedReqs = array_merge($completedReqs, $stageDetails['completed_requirements']);
            } elseif (! $hasCurrentStage) {
                $currentStageNumber = $stageNum;
                $currentStageName = $stageDetails['name'];
                $waitingOn = $stageDetails['waiting_on'];
                $nextAction = $stageDetails['next_action'];
                $blockers = $stageDetails['blockers'];
                $pendingReqs = $stageDetails['pending_requirements'];
                $hasCurrentStage = true;
            }

            if (! $stageDetails['is_completed']) {
                $previousStagesCompleted = false;
            }
        }

        $percentage = $requiredStageCount > 0
            ? (int) round(($completedStageCount / $requiredStageCount) * 100)
            : 0;

        return [
            'current_stage' => $currentStageNumber,
            'current_stage_name' => $currentStageName,
            'stage_status' => $blockers !== [] ? 'blocked' : ($percentage >= 100 ? 'completed' : 'in_progress'),
            'percentage' => $percentage,
            'required_stage_count' => $requiredStageCount,
            'completed_stage_count' => $completedStageCount,
            'completed_requirements' => array_values(array_unique($completedReqs)),
            'pending_requirements' => array_values(array_unique($pendingReqs)),
            'blockers' => $blockers,
            'waiting_on' => $waitingOn,
            'next_action' => $nextAction,
            'stages' => $stages,
        ];
    }

    /**
     * @param  Collection<string, OfficialFormInstance>  $instances
     * @param  Collection<string, Collection<int, OfficialFormInstance>>  $instanceGroups
     * @param  Collection<string, ResearchGroupMilestone>  $milestones
     * @return array<string, mixed>
     */
    private function evaluateStage(
        int $stageNum,
        ResearchClassGroup $group,
        $instances,
        $instanceGroups,
        $milestones
    ): array {
        if ($stageNum === 1) {
            return $this->evaluateTitlePresentationStage($group, $instances, $milestones);
        }

        if ($stageNum === 2) {
            return $this->evaluateProposalFormulationStage($group, $instances, $milestones);
        }

        if ($stageNum === 5) {
            return $this->evaluateSurveyInstrumentValidationStage($group, $instances, $milestones);
        }

        if ($stageNum === 6) {
            return $this->evaluateCompleteProposalSubmissionStage($group, $instances, $milestones);
        }

        $stageConfig = $this->getStageConfig($stageNum);
        $code = $stageConfig['code'];
        $name = $stageConfig['name'];

        $formCode = $stageConfig['primary_form'];
        $instance = $formCode === 'res-037'
            ? $this->defenseFormForStage($instanceGroups->get($formCode, collect()), $stageNum)
            : $instances->get($formCode);
        $milestone = $milestones->get($code);

        $isMilestoneCompleted = $milestone !== null && $milestone->status === ResearchMilestoneStatus::Completed;

        $isFormCompleted = $instance !== null && in_array($instance->status, ['approved', 'completed', 'signed'], true);
        $defense = $this->defenseForStage($group, $stageNum);
        $isDefenseCompleted = $defense !== null && in_array($defense->status, ['completed', 'finalized', 'released'], true);

        $isCompleted = $isMilestoneCompleted
            || $isDefenseCompleted
            || ($isFormCompleted && $stageConfig['form_completes_stage']);

        $completedReqs = [];
        $pendingReqs = [];
        $blockers = [];
        $waitingOn = null;
        $nextAction = null;

        if ($instance !== null) {
            $status = $instance->status;
            if (in_array($status, ['approved', 'completed', 'signed'], true)) {
                $completedReqs[] = "Official Form {$formCode} is ".ucfirst($status);
            } else {
                $pendingReqs[] = "Official Form {$formCode} signature/approval ({$status})";
                $waitingOn = $this->determineWaitingOnActor($formCode, $status);
                $nextAction = [
                    'label' => "Complete {$formCode} (".ucfirst($status).')',
                    'route' => route('official-forms.workspace.show', $instance->id),
                    'form_code' => $formCode,
                    'actor_type' => $this->determineActorForFormStatus($formCode, $status),
                ];
            }
        } else {
            $pendingReqs[] = "Initiate Official Form {$formCode}";
            $nextAction = [
                'label' => "Initiate {$formCode}",
                'route' => route('official-forms.workspace.index'),
                'form_code' => $formCode,
                'action_type' => 'form',
                'actor_type' => 'student_researcher',
            ];
        }

        if ($isDefenseCompleted) {
            $completedReqs[] = $name.' defense workflow is completed';
            $pendingReqs = [];
            $waitingOn = null;
            $nextAction = null;
        }

        return [
            'stage' => $stageNum,
            'code' => $code,
            'name' => $name,
            'is_completed' => $isCompleted,
            'primary_form' => $formCode,
            'form_status' => $instance ? $instance->status : 'not_started',
            'waiting_on' => $waitingOn,
            'next_action' => $nextAction,
            'completed_requirements' => $completedReqs,
            'pending_requirements' => $pendingReqs,
            'blockers' => $blockers,
        ];
    }

    /** @return array<string, mixed> */
    private function evaluateTitlePresentationStage(ResearchClassGroup $group, Collection $instances, Collection $milestones): array
    {
        $instance = $instances->get('res-026');
        $presentation = $instance?->titlePresentation;
        $milestone = $milestones->get('research-title-presentation');
        $document = Document::query()
            ->where('research_class_group_id', $group->id)
            ->where('document_stage', DocumentStage::TitleProposal->value)
            ->where('is_current', true)
            ->latest('version_number')
            ->first();

        $completed = [];
        $pending = [];
        $waitingOn = null;
        $nextLabel = null;
        $nextActionType = 'document';
        $actorType = 'student_group_leader';

        if ($document === null) {
            $pending[] = 'Upload Title Proposal document';
            $nextLabel = 'Upload Title Proposal Document';
        } elseif ($document->status === DocumentStatus::Draft) {
            $completed[] = 'Title Proposal document uploaded';
            $pending[] = 'Submit document for facilitator screening';
            $nextLabel = 'Submit Title Proposal for Screening';
        } elseif ($document->status === DocumentStatus::Submitted) {
            $completed[] = 'Title Proposal document submitted';
            $pending[] = 'Facilitator screening';
            $waitingOn = 'Research Facilitator screening';
            $actorType = 'research_facilitator';
        } elseif ($document->status === DocumentStatus::RevisionRequested || $document->status === DocumentStatus::Rejected) {
            $completed[] = 'Title Proposal screening decision recorded';
            $pending[] = 'Upload a new revised Title Proposal version';
            $nextLabel = 'Upload Revised Title Proposal';
        } else {
            $completed[] = 'Title Proposal approved for Title Presentation';
            if ($instance === null) {
                $pending[] = 'Create RES-026';
                $nextLabel = 'Create RES-026';
                $nextActionType = 'form';
            } elseif ($instance->status === 'draft') {
                $completed[] = 'RES-026 created';
                $pending[] = 'Enter and submit exactly three proposed titles';
                $nextLabel = 'Complete RES-026';
                $nextActionType = 'form';
            } elseif ($presentation === null) {
                $completed[] = 'RES-026 submitted';
                $pending[] = 'Schedule Title Presentation';
                $waitingOn = 'Research Facilitator to schedule Title Presentation';
                $actorType = 'research_facilitator';
            } elseif ($presentation->status === 'scheduled') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation scheduled']);
                $pending[] = 'Assign Chairperson and two Panel Members';
                $waitingOn = 'Research Facilitator to assign the Title Panel';
                $actorType = 'research_facilitator';
            } elseif ($presentation->status === 'panel_assigned') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation scheduled', 'Title Panel assigned']);
                $pending[] = 'Attend and complete Title Presentation';
                $nextLabel = 'Attend Title Presentation';
                $nextActionType = 'presentation';
                $actorType = 'research_group';
            } elseif ($presentation->status === 'presented') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation scheduled', 'Title Panel assigned', 'Title Presentation completed']);
                $pending[] = 'Record Approved Research Title No.';
                $waitingOn = 'Research Facilitator to record the approved title number';
                $actorType = 'research_facilitator';
            } elseif ($presentation->status === 'awaiting_panel_signatures') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation completed', 'Approved Research Title No. recorded']);
                $pending[] = 'Three assigned Panel signatures';
                $waitingOn = 'Assigned Title Panel signatures';
                $actorType = 'title_panel';
            } elseif ($presentation->status === 'awaiting_program_coordinator') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation completed', 'Panel signatures completed']);
                $pending[] = 'Program Coordinator signature';
                $waitingOn = 'Program Coordinator';
                $actorType = 'program_coordinator';
            } elseif ($presentation->status === 'awaiting_dean') {
                $completed = array_merge($completed, ['RES-026 submitted', 'Title Presentation completed', 'Panel signatures completed', 'Program Coordinator signed']);
                $pending[] = 'College Dean final action';
                $waitingOn = 'College Dean';
                $actorType = 'dean';
            } else {
                $completed = array_merge($completed, ['RES-026 approved', 'Canonical research title finalized']);
                if ($milestone?->status !== ResearchMilestoneStatus::Completed) {
                    $pending[] = 'Facilitator explicitly completes the Research Title Presentation milestone';
                    $waitingOn = 'Research Facilitator milestone completion';
                    $actorType = 'research_facilitator';
                }
            }
        }

        $isCompleted = $milestone?->status === ResearchMilestoneStatus::Completed;

        $nextRoute = match ($nextActionType) {
            'form' => $instance !== null
                ? route('official-forms.workspace.show', $instance)
                : route('official-forms.workspace.index', [
                    'form' => 'RES-026',
                    'group_id' => $group->id,
                ]),
            'presentation' => route('student.dashboard', ['tab' => 'defense']),
            default => route('student.dashboard', ['tab' => 'proposal']),
        };

        return [
            'stage' => 1,
            'code' => 'research-title-presentation',
            'name' => 'Research Title Presentation',
            'is_completed' => $isCompleted,
            'primary_form' => 'res-026',
            'form_status' => $instance?->status ?? 'not_started',
            'waiting_on' => $waitingOn,
            'next_action' => $nextLabel === null ? null : [
                'label' => $nextLabel,
                'route' => $nextRoute,
                'form_code' => $nextActionType === 'form' ? 'res-026' : null,
                'action_type' => $nextActionType,
                'actor_type' => $actorType,
                'document_label' => $nextActionType === 'document' ? 'Title Proposal Document' : null,
            ],
            'completed_requirements' => array_values(array_unique($completed)),
            'pending_requirements' => array_values(array_unique($pending)),
            'blockers' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function evaluateProposalFormulationStage(ResearchClassGroup $group, Collection $instances, Collection $milestones): array
    {
        $milestone = $milestones->get('formulation-research-proposal');
        $document = Document::query()
            ->where('research_class_group_id', $group->id)
            ->whereIn('document_stage', [
                DocumentStage::ProposalDefense->value,
                'proposal',
                'proposal_manuscript',
            ])
            ->where('is_current', true)
            ->latest('version_number')
            ->first();

        $completed = [];
        $pending = [];
        $waitingOn = null;
        $nextLabel = null;
        $nextRoute = route('student.dashboard', ['tab' => 'proposal']);
        $nextActionType = 'document';
        $actorType = 'student_researcher';

        // 1. Consultation Records (Ongoing cumulative log)
        $consultationCount = $group->consultationRecords()->where('is_superseded', false)->count();
        if ($consultationCount > 0) {
            $completed[] = "{$consultationCount} consultation session(s) logged on RES-031";
        }

        // 2. Proposal Document workflow
        if ($document === null) {
            $pending[] = 'Draft and upload Research Proposal document (Chapters 1-3)';
            $nextLabel = 'Upload Research Proposal';
            $waitingOn = 'Student Researchers to upload proposal document';
        } elseif ($document->status === DocumentStatus::Draft) {
            $completed[] = 'Research Proposal document uploaded';
            $pending[] = 'Submit Research Proposal for adviser review';
            $nextLabel = 'Submit Research Proposal';
            $waitingOn = 'Student Researchers to submit proposal';
        } elseif ($document->status === DocumentStatus::Submitted) {
            $completed[] = 'Research Proposal document submitted';
            $pending[] = 'Assigned Research Adviser review';
            $waitingOn = 'Assigned Research Adviser review';
            $actorType = 'adviser';
        } elseif ($document->status === DocumentStatus::RevisionRequested || $document->status === DocumentStatus::Rejected) {
            $completed[] = 'Adviser review feedback recorded';
            $pending[] = 'Upload revised Research Proposal';
            $nextLabel = 'Upload Revised Proposal';
            $waitingOn = 'Student Researchers to upload revised proposal';
        } else {
            $completed[] = 'Research Proposal approved by Research Adviser';
            if ($milestone?->status !== ResearchMilestoneStatus::Completed) {
                $pending[] = 'Facilitator/Adviser completes proposal formulation milestone';
                $waitingOn = 'Research Facilitator / Adviser milestone confirmation';
                $actorType = 'research_facilitator';
            }
        }

        $isMilestoneCompleted = $milestone !== null && $milestone->status === ResearchMilestoneStatus::Completed;
        $isDocApproved = $document !== null && in_array($document->status, [DocumentStatus::Accepted, DocumentStatus::ApprovedForPresentation], true);
        $isCompleted = $isMilestoneCompleted || $isDocApproved;

        return [
            'stage' => 2,
            'code' => 'formulation-research-proposal',
            'name' => 'Formulation of Research Proposal',
            'is_completed' => $isCompleted,
            'primary_form' => 'res-031',
            'form_status' => 'draft',
            'waiting_on' => $waitingOn,
            'next_action' => $nextLabel === null ? null : [
                'label' => $nextLabel,
                'route' => $nextRoute,
                'form_code' => null,
                'action_type' => $nextActionType,
                'actor_type' => $actorType,
                'document_label' => 'Research Proposal Document',
            ],
            'completed_requirements' => array_values(array_unique($completed)),
            'pending_requirements' => array_values(array_unique($pending)),
            'blockers' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function evaluateSurveyInstrumentValidationStage(ResearchClassGroup $group, Collection $instances, Collection $milestones): array
    {
        $milestone = $milestones->get('validation-survey-instrument');
        $isMilestoneCompleted = $milestone !== null && $milestone->status === ResearchMilestoneStatus::Completed;

        $res042 = $instances->get('res-042');
        $res043b = $instances->get('res-043b');
        $isFormCompleted = $res043b !== null && in_array($res043b->status, ['completed', 'validated', 'approved', 'signed'], true);

        $isCompleted = $isMilestoneCompleted || $isFormCompleted;

        $completed = [];
        $pending = [];
        $waitingOn = null;
        $nextLabel = null;
        $nextRoute = null;
        $formCode = 'res-042';
        $actorType = 'student_researcher';

        if ($isCompleted) {
            $completed[] = 'Survey instrument validation completed on RES-043B';
        } elseif ($res042 === null) {
            $pending[] = 'Submit Request for Instrument Validation (RES-042)';
            $nextLabel = 'Submit RES-042 (Instrument Validation Request)';
            $nextRoute = route('official-forms.workspace.index', [
                'form' => 'RES-042',
                'group_id' => $group->id,
            ]);
            $waitingOn = 'Student Researchers to request instrument validation';
        } elseif ($res042->status === 'draft') {
            $completed[] = 'RES-042 draft created';
            $pending[] = 'Submit RES-042 for validator assignment';
            $nextLabel = 'Submit RES-042';
            $nextRoute = route('official-forms.workspace.show', $res042->id);
            $waitingOn = 'Student Researchers to submit RES-042';
        } elseif ($res043b === null) {
            $completed[] = 'Instrument Validation Request (RES-042) submitted';
            $pending[] = 'Appointed Validator rating (RES-043A/B)';
            $waitingOn = 'Appointed Instrument Validator to complete validation rating (RES-043A/B)';
            $formCode = 'res-043b';
            $actorType = 'instrument_validator';
        } else {
            $completed[] = 'Instrument Validation Request (RES-042) submitted';
            $pending[] = "Instrument Validator action ({$res043b->status})";
            $waitingOn = 'Appointed Instrument Validator to finalize RES-043B';
            $nextLabel = 'Complete RES-043B Rating';
            $nextRoute = route('official-forms.workspace.show', $res043b->id);
            $formCode = 'res-043b';
            $actorType = 'instrument_validator';
        }

        return [
            'stage' => 5,
            'code' => 'validation-survey-instrument',
            'name' => 'Validation of Survey Instrument',
            'is_completed' => $isCompleted,
            'primary_form' => $res043b ? 'res-043b' : 'res-042',
            'form_status' => $res043b ? $res043b->status : ($res042 ? $res042->status : 'not_started'),
            'waiting_on' => $waitingOn,
            'next_action' => $nextLabel === null ? null : [
                'label' => $nextLabel,
                'route' => $nextRoute,
                'form_code' => $formCode,
                'action_type' => 'form',
                'actor_type' => $actorType,
            ],
            'completed_requirements' => array_values(array_unique($completed)),
            'pending_requirements' => array_values(array_unique($pending)),
            'blockers' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function evaluateCompleteProposalSubmissionStage(ResearchClassGroup $group, Collection $instances, Collection $milestones): array
    {
        $milestone = $milestones->get('submission-complete-research-proposal');
        $revisionMilestone = $milestones->get('revision-research-proposal');
        $res041 = $instances->get('res-041');
        $revisionCompletedAt = $res041?->updated_at ?? $revisionMilestone?->completed_at;

        $documentQuery = Document::query()
            ->where('research_class_group_id', $group->id)
            ->where('document_stage', DocumentStage::ProposalDefense->value)
            ->where('is_current', true);

        if ($revisionCompletedAt !== null) {
            $documentQuery->where('submitted_at', '>=', $revisionCompletedAt);
        }

        $document = $documentQuery->latest('version_number')->first();
        $isMilestoneCompleted = $milestone?->status === ResearchMilestoneStatus::Completed;
        $isDocumentSubmitted = $document !== null && ! in_array($document->status, [
            DocumentStatus::Draft,
            DocumentStatus::RevisionRequested,
            DocumentStatus::Rejected,
        ], true);

        $completed = [];
        $pending = [];
        $waitingOn = null;
        $nextAction = null;

        if ($isMilestoneCompleted || $isDocumentSubmitted) {
            $completed[] = 'Complete Research Proposal Paper submitted';
        } elseif ($document === null) {
            $pending[] = 'Upload the complete revised Research Proposal Paper';
            $waitingOn = 'Student Group Leader to submit the complete proposal paper';
            $nextAction = [
                'label' => 'Upload Complete Research Proposal Paper',
                'route' => route('student.dashboard', ['tab' => 'proposal']),
                'form_code' => null,
                'action_type' => 'document',
                'actor_type' => 'student_group_leader',
                'document_label' => 'Complete Research Proposal Paper',
            ];
        } elseif (in_array($document->status, [DocumentStatus::RevisionRequested, DocumentStatus::Rejected], true)) {
            $completed[] = 'Complete proposal paper reviewed';
            $pending[] = 'Upload the corrected complete Research Proposal Paper';
            $waitingOn = 'Student Group Leader to address the document review';
            $nextAction = [
                'label' => 'Upload Corrected Research Proposal Paper',
                'route' => route('student.dashboard', ['tab' => 'proposal']),
                'form_code' => null,
                'action_type' => 'document',
                'actor_type' => 'student_group_leader',
                'document_label' => 'Corrected Complete Research Proposal Paper',
            ];
        } else {
            $pending[] = 'Submit the complete Research Proposal Paper';
            $waitingOn = 'Student Group Leader to submit the complete proposal paper';
            $nextAction = [
                'label' => 'Submit Complete Research Proposal Paper',
                'route' => route('student.dashboard', ['tab' => 'proposal']),
                'form_code' => null,
                'action_type' => 'document',
                'actor_type' => 'student_group_leader',
                'document_label' => 'Complete Research Proposal Paper',
            ];
        }

        return [
            'stage' => 6,
            'code' => 'submission-complete-research-proposal',
            'name' => 'Submission of Complete Research Proposal Paper',
            'is_completed' => $isMilestoneCompleted || $isDocumentSubmitted,
            'primary_form' => 'res-041',
            'form_status' => $res041?->status ?? 'not_started',
            'waiting_on' => $waitingOn,
            'next_action' => $nextAction,
            'completed_requirements' => $completed,
            'pending_requirements' => $pending,
            'blockers' => [],
        ];
    }

    /** @param Collection<int, OfficialFormInstance> $instances */
    private function defenseFormForStage(Collection $instances, int $stageNum): ?OfficialFormInstance
    {
        $expectedTypes = $this->defenseTypesForStage($stageNum);
        if ($expectedTypes === []) {
            return null;
        }

        $matching = $instances
            ->filter(fn (OfficialFormInstance $instance): bool => in_array(
                $this->defenseTypeForForm($instance),
                $expectedTypes,
                true,
            ))
            ->sortByDesc('id');

        if ($matching->isNotEmpty()) {
            return $matching->first();
        }

        // Older proposal-defense forms may not have a typed source. Do not use
        // that fallback for later defenses because it would mislabel RES-037.
        return $stageNum === 3
            ? $instances->filter(fn (OfficialFormInstance $instance): bool => $this->defenseTypeForForm($instance) === null)
                ->sortByDesc('id')
                ->first()
            : null;
    }

    private function defenseForStage(ResearchClassGroup $group, int $stageNum): ?Defense
    {
        $expectedTypes = $this->defenseTypesForStage($stageNum);
        if ($expectedTypes === []) {
            return null;
        }

        return $group->defenses
            ->filter(fn (Defense $defense): bool => in_array($defense->defense_type, $expectedTypes, true))
            ->sortByDesc('id')
            ->first();
    }

    /** @return list<string> */
    private function defenseTypesForStage(int $stageNum): array
    {
        return match ($stageNum) {
            3 => ['proposal_defense', 'proposal'],
            10 => ['pre_final_defense', 'pre_final'],
            11 => ['final_defense', 'final_oral_defense', 'final'],
            default => [],
        };
    }

    private function defenseTypeForForm(OfficialFormInstance $instance): ?string
    {
        $source = $instance->source;

        return match (true) {
            $source instanceof DefenseEvaluationRound => $source->defense?->defense_type,
            $source instanceof DefenseSchedule => $source->defense?->defense_type,
            default => null,
        };
    }

    /** @param Collection<int, OfficialFormInstance> $instances */
    private function furthestReachedStage(ResearchClassGroup $group, Collection $instances): int
    {
        $furthestStage = 1;

        foreach ($instances as $instance) {
            if (in_array($instance->status, ['cancelled', 'superseded'], true)) {
                continue;
            }

            $form = OfficialResearchWorkflowRegistry::FORMS[strtolower($instance->definition->code)] ?? null;
            if ($form !== null) {
                $furthestStage = max($furthestStage, (int) $form['stage']);
            }
        }

        foreach ($group->defenses as $defense) {
            if ($defense->status === 'cancelled') {
                continue;
            }

            $stage = match ($defense->defense_type) {
                'proposal_defense', 'proposal' => 3,
                'pre_final_defense', 'pre_final' => 10,
                'final_defense', 'final_oral_defense', 'final' => 11,
                default => 1,
            };
            $furthestStage = max($furthestStage, $stage);
        }

        $documentStage = Document::query()
            ->where('research_class_group_id', $group->id)
            ->where('is_current', true)
            ->whereIn('status', [
                DocumentStatus::Submitted->value,
                DocumentStatus::UnderReview->value,
                DocumentStatus::ApprovedForPresentation->value,
                DocumentStatus::Accepted->value,
            ])
            ->pluck('document_stage')
            ->map(function (DocumentStage|string|null $stage): int {
                $stageValue = $stage instanceof DocumentStage ? $stage->value : $stage;

                return match ($stageValue) {
                    DocumentStage::ProposalDefense->value => 2,
                    DocumentStage::PreFinalDefense->value => 10,
                    DocumentStage::FinalDefense->value => 11,
                    DocumentStage::FinalManuscript->value => 14,
                    default => 1,
                };
            })
            ->max();

        return max($furthestStage, (int) ($documentStage ?? 1));
    }

    private function determineWaitingOnActor(string $formCode, string $status): string
    {
        return match ($formCode) {
            'res-026' => match ($status) {
                'draft' => 'Student Researcher submission',
                'submitted' => 'Research Facilitator screening',
                'in_progress' => 'Program Coordinator signature',
                'endorsed' => 'College Dean approval',
                default => 'Review',
            },
            'res-027' => 'Invited Adviser acceptance',
            'res-033' => 'Thesis Adviser defense endorsement',
            'res-036', 'res-037' => 'Defense Panel evaluations & summary',
            'res-040' => 'Research Instructor receipt',
            'res-041' => 'Program Coordinator receipt',
            'res-043a', 'res-043b' => 'Instrument Validator rating',
            'res-045' => 'Language Editor certification',
            'res-046' => 'Technical Editor certification',
            'res-047' => 'College Dean reproduction clearance',
            'res-049' => 'Co-author student signatures',
            default => 'Assigned Reviewer',
        };
    }

    /** @return list<int> */
    public function automaticStageNumbersFor(ResearchClassGroup $group): array
    {
        return $this->resolveProgramCode($group) === 'BSIT'
            ? self::BSIT_AUTOMATIC_STAGE_NUMBERS
            : [];
    }

    private function resolveProgramCode(ResearchClassGroup $group): ?string
    {
        // Some dashboards preload a reduced student column set for display. Force
        // this canonical curriculum relation to reload so progress never depends
        // on which screen happened to hydrate the group first.
        $group->load('members.student.studentProfile.program');

        $programCodes = $group->members
            ->map(function ($member): ?string {
                $programCode = strtoupper(trim((string) $member->student?->studentProfile?->program?->code));

                if (in_array($programCode, ['BSIT', 'IT'], true)) {
                    return 'BSIT';
                }

                if (in_array($programCode, ['BSCS', 'CS'], true)) {
                    return 'BSCS';
                }

                $legacyProgram = strtoupper(trim((string) $member->student?->program));

                if (str_contains($legacyProgram, 'BSIT') || str_contains($legacyProgram, 'INFORMATION TECHNOLOGY')) {
                    return 'BSIT';
                }

                if (str_contains($legacyProgram, 'BSCS') || str_contains($legacyProgram, 'COMPUTER SCIENCE')) {
                    return 'BSCS';
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();

        return $programCodes->count() === 1 ? $programCodes->first() : null;
    }

    private function determineActorForFormStatus(string $formCode, string $status): string
    {
        return match ($formCode) {
            'res-026' => match ($status) {
                'submitted' => 'facilitator',
                'in_progress' => 'program_coordinator',
                'endorsed' => 'dean',
                default => 'student_researcher',
            },
            'res-027' => 'adviser',
            'res-033', 'res-040', 'res-044', 'res-047' => 'adviser',
            'res-041' => 'program_coordinator',
            'res-043a', 'res-043b' => 'instrument_validator',
            'res-045' => 'language_editor',
            'res-046' => 'technical_editor',
            'res-049' => 'student_researcher',
            default => 'facilitator',
        };
    }

    /** @return array{code: string, name: string, primary_form: string, form_completes_stage: bool, optional?: bool} */
    private function getStageConfig(int $stageNum): array
    {
        return match ($stageNum) {
            1 => ['code' => 'research-title-presentation', 'name' => 'Research Title Presentation', 'primary_form' => 'res-026', 'form_completes_stage' => false],
            2 => ['code' => 'formulation-research-proposal', 'name' => 'Formulation of Research Proposal', 'primary_form' => 'res-031', 'form_completes_stage' => false],
            3 => ['code' => 'research-proposal-defense', 'name' => 'Research Proposal Defense', 'primary_form' => 'res-037', 'form_completes_stage' => true],
            4 => ['code' => 'revision-research-proposal', 'name' => 'Revision of Research Proposal Paper', 'primary_form' => 'res-041', 'form_completes_stage' => true],
            5 => ['code' => 'validation-survey-instrument', 'name' => 'Validation of Survey Instrument', 'primary_form' => 'res-043b', 'form_completes_stage' => true],
            6 => ['code' => 'submission-complete-research-proposal', 'name' => 'Submission of Complete Research Proposal Paper', 'primary_form' => 'res-041', 'form_completes_stage' => false],
            7 => ['code' => 'data-gathering', 'name' => 'Data Gathering', 'primary_form' => 'res-044', 'form_completes_stage' => true],
            8 => ['code' => 'data-processing', 'name' => 'Data Processing', 'primary_form' => 'res-044', 'form_completes_stage' => false],
            9 => ['code' => 'report-writing', 'name' => 'Report Writing', 'primary_form' => 'res-033', 'form_completes_stage' => false],
            10 => ['code' => 'research-pre-final-defense', 'name' => 'Research Pre-Final Defense', 'primary_form' => 'res-037', 'form_completes_stage' => true],
            11 => ['code' => 'research-final-oral-defense', 'name' => 'Research Final/Oral Defense', 'primary_form' => 'res-037', 'form_completes_stage' => true],
            12 => ['code' => 'revision-whole-research-paper', 'name' => 'Revision of Whole Research Paper', 'primary_form' => 'res-039', 'form_completes_stage' => false],
            13 => ['code' => 'language-technical-editing', 'name' => 'Language and Technical Editing', 'primary_form' => 'res-047', 'form_completes_stage' => true, 'optional' => true],
            14 => ['code' => 'submission-final-research-paper', 'name' => 'Submission of Final Copy of Research Paper', 'primary_form' => 'res-049', 'form_completes_stage' => true],
            default => ['code' => 'research-title-presentation', 'name' => 'Research Title Presentation', 'primary_form' => 'res-026', 'form_completes_stage' => true],
        };
    }
}
