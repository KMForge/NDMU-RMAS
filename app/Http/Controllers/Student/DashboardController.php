<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ConsultationRequest;
use App\Models\Document;
use App\Models\ResearchClassGroupMember;
use App\Modules\Consultations\Queries\GetStudentConsultationData;
use App\Modules\DefenseScheduling\Queries\GetDefenseScheduleCalendar;
use App\Modules\Documents\Queries\GetDocumentRepositoryData;
use App\Modules\Documents\Services\ExtractPaperMetadata;
use App\Modules\Evaluations\Queries\GetEvaluationRoundData;
use App\Modules\Notifications\Queries\GetNotificationsForUser;
use App\Modules\Notifications\Services\UnreadNotificationCount;
use App\Modules\OfficialForms\Services\GetPendingAcademicActionsForUser;
use App\Modules\Research\Queries\GetStudentDashboardData;
use App\Modules\SystemSettings\Services\DocumentUploadLimit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        GetStudentDashboardData $dashboardData,
        GetDocumentRepositoryData $repositoryData,
        GetStudentConsultationData $consultationData,
        GetDefenseScheduleCalendar $defenseCalendar,
        GetEvaluationRoundData $evaluationQuery,
        GetPendingAcademicActionsForUser $pendingActionsService,
        GetNotificationsForUser $notificationQuery,
        DocumentUploadLimit $documentUploadLimit,
    ): View {
        $allowedTabs = [
            'dashboard',
            'classes',
            'research',
            'proposal',
            'progress',
            'consultation',
            'revisions',
            'defense',
            'evaluations',
            'repository',
            'forms',
            'notifications',
            'settings',
        ];
        $activeTab = in_array($request->query('tab'), $allowedTabs, true)
            ? (string) $request->query('tab')
            : 'dashboard';
        $unreadNotificationCount = app(UnreadNotificationCount::class)->for($request->user());
        $data = $dashboardData->for(
            $request->user(),
            $request->query('dashboard_q'),
            $activeTab,
        );

        if ($activeTab === 'repository') {
            $data = [...$data, ...$repositoryData->for($request->user(), $request->query())];
            $data['documents'] = $data['repositoryDocuments'];
        }

        if ($activeTab === 'consultation') {
            $data = [...$data, ...$consultationData->for($request->user())];
        }

        if ($activeTab === 'notifications') {
            $data['userNotifications'] = $notificationQuery->execute($request->user(), (string) $request->query('notification_filter', 'all'));
            $data['userUnreadCount'] = $unreadNotificationCount;
            $data['notificationFilter'] = (string) $request->query('notification_filter', 'all');
        } else {
            $data['userNotifications'] = collect();
            $data['userUnreadCount'] = $unreadNotificationCount;
            $data['notificationFilter'] = 'all';
        }

        $pendingConsultationsCount = (! app()->environment('testing') || Schema::hasTable('consultation_requests'))
            ? ConsultationRequest::query()
                ->where('requested_by', $request->user()->getKey())
                ->whereIn('status', ['pending', 'reschedule_proposed'])
                ->count()
            : 0;

        $data['pendingConsultationsCount'] = $pendingConsultationsCount;
        $data['defenses'] = $activeTab === 'defense'
            ? $defenseCalendar->execute($request->user())
            : collect();
        $data['releasedEvaluations'] = $activeTab === 'evaluations'
            ? ($evaluationQuery->forStudent($request->user())['rounds'] ?? [])
            : [];
        $data['evaluations'] = $data['releasedEvaluations'];
        $releasedEvaluationCount = $activeTab === 'evaluations'
            ? count($data['releasedEvaluations'])
            : $evaluationQuery->releasedCountForStudent($request->user());
        $defenseBadgeCount = in_array($activeTab, ['dashboard', 'defense'], true)
            ? collect($data['defenses'])
                ->whereNotIn('defense_status', ['completed', 'cancelled'])
                ->whereNotIn('schedule_status', ['completed', 'cancelled'])
                ->count()
            : $defenseCalendar->activeCount($request->user());
        $data['officialFormPhases'] = config('official-forms.phases', []);
        $data['officialForms'] = ($activeTab === 'forms' ? collect(config('official-forms.student', [])) : collect())
            ->filter(fn (array $form, string $code) => $request->user()->getAllPermissions()
                ->contains(fn ($permission) => str_starts_with($permission->name, 'forms.'.strtolower($code).'.')))
            ->map(function (array $form, string $code): array {
                unset($form['file']);

                return [
                    ...$form,
                    'source_url' => route('student.official-forms.source', ['form' => $code]),
                ];
            })
            ->all();

        $journey = $data['journey'] ?? null;
        $pendingAcademicActions = $pendingActionsService->execute($request->user());

        $data['sidebarBadges'] = [
            'classes' => count($data['classes'] ?? []),
            'consultation' => $pendingConsultationsCount,
            'revisions' => collect($data['revisions'] ?? [])
                ->whereIn('status', ['open', 'in_progress'])
                ->count() + (int) ($data['documentFeedbackCount'] ?? 0),
            'defense' => $defenseBadgeCount,
            'evaluations' => $releasedEvaluationCount,
            'forms' => $pendingAcademicActions->count(),
            'notifications' => $unreadNotificationCount,
        ];

        return view('pages.student-dashboard', [
            'area' => 'Student Portal',
            'student' => $request->user(),
            'activeDashboardTab' => $activeTab,
            'journey' => $journey,
            'pendingAcademicActions' => $pendingAcademicActions,
            'documentMaxUploadMb' => $documentUploadLimit->megabytes(),
            ...$data,
        ]);
    }

    public function fetchPaperMetadata(Request $request, ExtractPaperMetadata $extractor): RedirectResponse
    {
        $user = $request->user();
        $groupMember = ResearchClassGroupMember::query()
            ->where('student_id', $user->getKey())
            ->whereHas('researchClassGroup', fn ($q) => $q->where('status', 'active'))
            ->whereHas('researchClassEnrollment', fn ($q) => $q->where('status', 'active'))
            ->with('researchClassGroup')
            ->first();

        if ($groupMember === null || $groupMember->researchClassGroup === null) {
            return to_route('student.dashboard', ['tab' => 'research'])
                ->with('research_error', 'No active research group found.');
        }

        $document = Document::query()
            ->where('research_class_group_id', $groupMember->research_class_group_id)
            ->where('is_current', true)
            ->latest('submitted_at')
            ->first();

        if ($document === null) {
            return to_route('student.dashboard', ['tab' => 'research'])
                ->with('research_error', 'No current research paper found to extract metadata from.');
        }

        $project = $extractor->extractAndSync($document);

        if ($project !== null && (! empty($project->abstract) || ! empty($project->keywords))) {
            return to_route('student.dashboard', ['tab' => 'research'])
                ->with('research_success', 'Abstract and keywords successfully fetched from your research paper.');
        }

        return to_route('student.dashboard', ['tab' => 'research'])
            ->with('research_error', 'Could not detect an "Abstract" or "Keywords" section in the paper.');
    }
}
