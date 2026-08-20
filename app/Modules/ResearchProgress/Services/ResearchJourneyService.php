<?php

namespace App\Modules\ResearchProgress\Services;

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
            ->with(['definition', 'actorAssignments.user'])
            ->get()
            ->keyBy(fn ($i) => strtolower($i->definition->code));

        $milestones = ResearchGroupMilestone::query()
            ->where('research_class_group_id', $group->id)
            ->get()
            ->keyBy('milestone_code');

        $stages = [];
        $completedStageCount = 0;
        $currentStageNumber = 1;
        $currentStageName = 'Research Title Presentation';
        $waitingOn = null;
        $nextAction = null;
        $blockers = [];
        $completedReqs = [];
        $pendingReqs = [];

        for ($stageNum = 1; $stageNum <= 13; $stageNum++) {
            $stageDetails = $this->evaluateStage($stageNum, $group, $instances, $milestones);
            $stages[$stageNum] = $stageDetails;

            if ($stageDetails['is_completed']) {
                $completedStageCount++;
                $completedReqs = array_merge($completedReqs, $stageDetails['completed_requirements']);
            } elseif ($currentStageNumber === 1 || ($stages[$stageNum - 1]['is_completed'] ?? false)) {
                $currentStageNumber = $stageNum;
                $currentStageName = $stageDetails['name'];
                $waitingOn = $stageDetails['waiting_on'];
                $nextAction = $stageDetails['next_action'];
                $blockers = $stageDetails['blockers'];
                $pendingReqs = $stageDetails['pending_requirements'];
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
        $stageConfig = $this->getStageConfig($stageNum);
        $code = $stageConfig['code'];
        $name = $stageConfig['name'];

        $formCode = $stageConfig['primary_form'];
        $instance = $instances->get($formCode);
        $milestone = $milestones->get($code);

        $isMilestoneCompleted = $milestone !== null && $milestone->status === 'completed';
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
            1 => ['code' => 'research-title-presentation', 'name' => 'Research Title Presentation', 'primary_form' => 'res-026', 'form_completes_stage' => true],
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
