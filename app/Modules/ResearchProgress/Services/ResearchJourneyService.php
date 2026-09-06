<?php

namespace App\Modules\ResearchProgress\Services;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\ResearchMilestoneStatus;
use App\Models\DefenseEvaluationRound;
use App\Models\DefenseSchedule;
use App\Models\Document;
use App\Models\OfficialFormInstance;
use App\Models\ResearchClassGroup;
use App\Models\ResearchGroupMilestone;
use App\Models\User;
use Illuminate\Support\Collection;

class ResearchJourneyService
{
    /**
     * @return array<string, mixed>
     */
    public function getJourneyForGroup(ResearchClassGroup $group, ?User $user = null): array
    {
        $group->loadMissing([
            'members.student', 'adviser', 'researchClass',
        ]);

        $instances = OfficialFormInstance::query()
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
            ->get()
            ->keyBy(fn ($i) => strtolower($i->definition->code));

        $milestones = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->id)
            ->with('definition:id,code')
            ->get()
            ->filter(fn (ResearchGroupMilestone $milestone): bool => $milestone->definition !== null)
            ->keyBy(fn (ResearchGroupMilestone $milestone): string => $milestone->definition->code);

        $stages = [];
        $completedStageCount = 0;
        $currentStageNumber = 1;
        $currentStageName = 'Research Title Presentation';
        $waitingOn = null;
        $nextAction = null;
        $blockers = [];
        $completedReqs = [];
        $pendingReqs = [];
        $hasCurrentStage = false;

        for ($stageNum = 1; $stageNum <= 13; $stageNum++) {
            $stageDetails = $this->evaluateStage($stageNum, $group, $instances, $milestones);
            $stages[$stageNum] = $stageDetails;

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
        }

        $percentage = (int) round(($completedStageCount / 13) * 100);

        return [
            'current_stage' => $currentStageNumber,
            'current_stage_name' => $currentStageName,
            'stage_status' => $blockers !== [] ? 'blocked' : ($percentage >= 100 ? 'completed' : 'in_progress'),
            'percentage' => $percentage,
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
     * @param  Collection<string, ResearchGroupMilestone>  $milestones
     * @return array<string, mixed>
     */
    private function evaluateStage(
        int $stageNum,
        ResearchClassGroup $group,
        $instances,
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

        $stageConfig = $this->getStageConfig($stageNum);
        $code = $stageConfig['code'];
        $name = $stageConfig['name'];

        $formCode = $stageConfig['primary_form'];
        $instance = $instances->get($formCode);
        $milestone = $milestones->get($code);

        $isMilestoneCompleted = $milestone !== null && $milestone->status === ResearchMilestoneStatus::Completed;

        $isFormMatchingStage = true;
        if ($formCode === 'res-037' && $instance !== null) {
            $schedule = $instance->source instanceof DefenseSchedule ? $instance->source : ($instance->source instanceof DefenseEvaluationRound ? $instance->source->defenseSchedule : null);
            $defenseType = $schedule?->defense?->defense_type ?? $instance->source?->defense?->defense_type ?? '';
            if ($stageNum === 3 && in_array($defenseType, ['final_defense', 'final_oral_defense'], true)) {
                $isFormMatchingStage = false;
            } elseif ($stageNum === 10 && in_array($defenseType, ['proposal_defense', 'proposal', 'title_proposal', ''], true)) {
                $isFormMatchingStage = false;
            }
        }

        $isFormCompleted = $isFormMatchingStage && $instance !== null && in_array($instance->status, ['approved', 'completed', 'signed'], true);

        $isCompleted = $isMilestoneCompleted || ($isFormCompleted && $stageConfig['form_completes_stage']);

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

    /** @return array{code: string, name: string, primary_form: string, form_completes_stage: bool} */
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
            10 => ['code' => 'research-final-oral-defense', 'name' => 'Research Final/Oral Defense', 'primary_form' => 'res-037', 'form_completes_stage' => true],
            11 => ['code' => 'revision-whole-research-paper', 'name' => 'Revision of Whole Research Paper', 'primary_form' => 'res-039', 'form_completes_stage' => false],
            12 => ['code' => 'language-technical-editing', 'name' => 'Language and Technical Editing', 'primary_form' => 'res-047', 'form_completes_stage' => true],
            13 => ['code' => 'submission-final-research-paper', 'name' => 'Submission of Final Copy of Research Paper', 'primary_form' => 'res-049', 'form_completes_stage' => true],
            default => ['code' => 'research-title-presentation', 'name' => 'Research Title Presentation', 'primary_form' => 'res-026', 'form_completes_stage' => true],
        };
    }
}
