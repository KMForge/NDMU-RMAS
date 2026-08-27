<?php

namespace App\Modules\ResearchProgress\Services;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use App\Enums\ResearchMilestoneStatus;
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
            ->where('research_class_group_id', $group->id)
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

        $percentage = round(($completedStageCount / 13) * 100, 1);

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

        $stageConfig = $this->getStageConfig($stageNum);
        $code = $stageConfig['code'];
        $name = $stageConfig['name'];

        $formCode = $stageConfig['primary_form'];
        $instance = $instances->get($formCode);
        $milestone = $milestones->get($code);

        $isMilestoneCompleted = $milestone !== null && $milestone->status === ResearchMilestoneStatus::Completed;
        $isFormCompleted = $instance !== null && in_array($instance->status, ['approved', 'completed', 'signed'], true);

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
                'route' => $instance ? route('official-forms.workspace.show', $instance) : route('student.dashboard', ['tab' => 'proposal']),
                'form_code' => $nextActionType === 'form' ? 'res-026' : null,
                'action_type' => $nextActionType,
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
