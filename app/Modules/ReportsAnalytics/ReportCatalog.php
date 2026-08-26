<?php

namespace App\Modules\ReportsAnalytics;

use Illuminate\Support\Arr;

final class ReportCatalog
{
    public const VERSION = 1;

    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $common = ['academic_year_id', 'academic_term_id', 'program_id', 'research_class_id', 'adviser_id', 'date_from', 'date_to'];

        return [
            'research-stage-status' => $this->definition('Research by Stage and Status', 'Current lifecycle stage and status for each CEAC research group.', [...$common, 'stage', 'status'], ['Group', 'Class', 'Program', 'Adviser', 'Current Stage', 'Status']),
            'research-summary' => $this->definition('Research Status Summary', 'Active, completed, and overdue research groups. Delayed is unavailable because the schema has no distinct delayed state.', [...$common, 'status'], ['Status', 'Groups']),
            'milestone-completion' => $this->definition('Weighted Milestone Completion', 'Server-calculated completion using configured milestone weights; not-applicable milestones are excluded.', $common, ['Group', 'Class', 'Program', 'Applicable Weight', 'Completed Weight', 'Completion']),
            'research-output-program' => $this->definition('Research Output by Program', 'Current CEAC research groups grouped by program.', $common, ['Program', 'Research Groups', 'Completed', 'Overdue']),
            'research-throughput' => $this->definition('Research Throughput', 'Milestone completion events grouped by month.', $common, ['Month', 'Completed Milestones']),
            'defense-types' => $this->definition('Defense Type Status', 'Verified title presentation, proposal defense, pre-final defense, and final defense occurrences.', $common, ['Defense Type', 'Draft', 'Scheduled', 'Completed', 'Cancelled']),
            'defense-status' => $this->definition('Defense Status Summary', 'Current defense occurrences by authoritative lifecycle status.', $common, ['Status', 'Defenses']),
            'adviser-workload' => $this->definition('Adviser Workload', 'Current active group assignments per adviser.', $common, ['Adviser', 'Active Groups']),
            'document-review-status' => $this->definition('Document and Review Status', 'Current document versions and their latest non-superseded review state.', $common, ['Document Status', 'Documents', 'Reviewed', 'Awaiting Review']),
            'revision-summary' => $this->definition('Revision Summary', 'Revision requests by status, including unresolved requests past a real due date.', $common, ['Status', 'Requests', 'Overdue']),
            'evaluation-release-status' => $this->definition('Evaluation Release Status', 'Evaluation rounds grouped using their actual release timestamp.', $common, ['Release Status', 'Evaluation Rounds']),
        ];
    }

    /** @return array<string, mixed> */
    public function get(string $identifier): array
    {
        return Arr::get($this->all(), $identifier) ?? abort(404);
    }

    /** @param list<string> $filters @param list<string> $columns @return array<string, mixed> */
    private function definition(string $title, string $description, array $filters, array $columns): array
    {
        return compact('title', 'description', 'filters', 'columns') + ['version' => self::VERSION];
    }
}
