<?php

namespace App\View\Components;

use App\Models\User;
use App\Models\UserOnboardingCompletion;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class PortalOnboarding extends Component
{
    public bool $visible = false;

    public string $workspace = '';

    public string $firstName = 'User';

    /** @var array<int, array{title: string, description: string, icon: string}> */
    public array $steps = [];

    public function __construct()
    {
        $routeName = request()->route()?->getName();
        $workspace = is_string($routeName) ? str($routeName)->before('.')->toString() : '';
        $allowed = ['admin', 'facilitator', 'dean', 'adviser', 'panelist', 'student'];

        if (! in_array($workspace, $allowed, true) || $routeName !== "{$workspace}.dashboard") {
            return;
        }

        /** @var User|null $user */
        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $this->workspace = $workspace;
        $this->firstName = $user->displayFirstName();
        $this->steps = $this->stepsFor($workspace);
        $this->visible = ! UserOnboardingCompletion::query()
            ->where('user_id', $user->id)
            ->where('workspace', $workspace)
            ->exists();
    }

    public function render(): View
    {
        return view('components.portal-onboarding');
    }

    /** @return array<int, array{title: string, description: string, icon: string}> */
    private function stepsFor(string $workspace): array
    {
        $workspaceSteps = [
            'admin' => [
                ['title' => 'Manage accounts and access', 'description' => 'Use User Management and Roles & Permissions to control secure access.', 'icon' => 'ph-users-three'],
                ['title' => 'Oversee research operations', 'description' => 'Review institutional research, defenses, forms, and repository records.', 'icon' => 'ph-buildings'],
                ['title' => 'Review accountability records', 'description' => 'Reports and Audit Logs show activity, decisions, and system history.', 'icon' => 'ph-shield-check'],
            ],
            'facilitator' => [
                ['title' => 'Set up classes and groups', 'description' => 'Create Capstone classes, approve join requests, and organize research groups.', 'icon' => 'ph-chalkboard-teacher'],
                ['title' => 'Monitor every research journey', 'description' => 'Research Monitoring keeps milestones, requirements, and next actions together.', 'icon' => 'ph-chart-line-up'],
                ['title' => 'Coordinate forms and defenses', 'description' => 'Manage endorsements, schedules, official forms, statistics, and reports.', 'icon' => 'ph-calendar-check'],
            ],
            'dean' => [
                ['title' => 'Review academic approvals', 'description' => 'Open pending items that require college-level authorization or oversight.', 'icon' => 'ph-seal-check'],
                ['title' => 'Monitor research activity', 'description' => 'Track manuscripts, defenses, schedules, and institutional compliance.', 'icon' => 'ph-binoculars'],
                ['title' => 'Use reports for decisions', 'description' => 'View research outcomes and operational summaries across your scope.', 'icon' => 'ph-chart-bar'],
            ],
            'adviser' => [
                ['title' => 'Work with assigned researchers', 'description' => 'Open your classes and assigned groups to follow their research work.', 'icon' => 'ph-users'],
                ['title' => 'Review documents and revisions', 'description' => 'Record findings, request revisions, and monitor resubmissions.', 'icon' => 'ph-file-magnifying-glass'],
                ['title' => 'Guide consultations and endorsements', 'description' => 'Manage consultations, defense readiness, evaluations, and official forms.', 'icon' => 'ph-chats-circle'],
            ],
            'panelist' => [
                ['title' => 'Open assigned research papers', 'description' => 'Review the manuscripts assigned to your defense panel.', 'icon' => 'ph-files'],
                ['title' => 'Record precise feedback', 'description' => 'Attach page-based comments and recommendations to the student document.', 'icon' => 'ph-note-pencil'],
                ['title' => 'Complete defense evaluations', 'description' => 'Use your schedule and official forms to submit required scoring.', 'icon' => 'ph-clipboard-text'],
            ],
            'student' => [
                ['title' => 'Join your Capstone class', 'description' => 'Use the class code, wait for approval, and work with your research group.', 'icon' => 'ph-users-three'],
                ['title' => 'Follow your next research step', 'description' => 'The progress tracker shows completed stages, requirements, and your next action.', 'icon' => 'ph-path'],
                ['title' => 'Keep your work organized', 'description' => 'Submit documents, consultations, revisions, schedules, and official forms here.', 'icon' => 'ph-folder-open'],
            ],
        ];

        return [
            ['title' => "Welcome, {$this->firstName}!", 'description' => 'Let us take a quick tour of your workspace so you know where to begin.', 'icon' => 'ph-hand-waving'],
            ...$workspaceSteps[$workspace],
        ];
    }
}
