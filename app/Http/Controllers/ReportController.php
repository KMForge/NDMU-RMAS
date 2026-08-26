<?php

namespace App\Http\Controllers;

use App\Http\Requests\Reports\ReportFilterRequest;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use App\Modules\ReportsAnalytics\Exports\CsvReportExporter;
use App\Modules\ReportsAnalytics\Exports\PdfReportExporter;
use App\Modules\ReportsAnalytics\Queries\RunReport;
use App\Modules\ReportsAnalytics\ReportCatalog;
use App\Modules\ReportsAnalytics\Services\ResolveReportScope;
use App\Policies\ReportPolicy;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportCatalog $catalog,
        private readonly ResolveReportScope $scopes,
        private readonly RunReport $reports,
        private readonly AuditLogWriter $audit,
    ) {}

    public function index(ReportFilterRequest $request): View
    {
        $this->authorizeView($request);
        $scope = $this->scope($request);

        return view('pages.reports.index', ['reports' => $this->catalog->all(), 'scope' => $scope]);
    }

    public function show(ReportFilterRequest $request, string $report): View
    {
        $this->authorizeView($request);
        $scope = $this->scope($request);
        $filters = $request->filters();
        $definition = $this->catalog->get($report);
        $page = max(1, (int) $request->integer('page', 1));
        $perPage = 25;

        if ($this->reports->isDetailReport($report)) {
            $paginator = $this->reports->paginateDetail($report, $scope, $filters, $perPage, $page);
            $result = [
                'columns' => $definition['columns'],
                'rows' => $paginator->items(),
                'summary' => ['row_count' => $paginator->total()],
            ];
            $this->record($request, 'report.viewed', $report, $scope->label(), $paginator->total(), $filters->toArray());
        } else {
            $result = $this->reports->execute($report, $scope, $filters);
            $rows = collect($result['rows']);
            $paginator = new LengthAwarePaginator(
                $rows->forPage($page, $perPage)->values(),
                $rows->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $this->record($request, 'report.viewed', $report, $scope->label(), count($result['rows']), $filters->toArray());
        }

        return view('pages.reports.show', compact('report', 'definition', 'result', 'scope', 'filters', 'paginator') + [
            'options' => $this->reports->filterOptions($scope),
            'milestoneDefinitions' => DB::table('milestone_definitions')->where('is_active', true)->orderBy('sequence')->get(['code', 'name']),
        ]);
    }

    public function csv(ReportFilterRequest $request, string $report, CsvReportExporter $exporter): StreamedResponse
    {
        $this->authorizeExport($request);
        $scope = $this->scope($request);
        $filters = $request->filters();
        $definition = $this->catalog->get($report);

        if ($this->reports->isDetailReport($report)) {
            $count = $this->reports->countDetail($report, $scope, $filters);
            $exporter->guardCount($count);
            $rows = $this->reports->getDetailRows($report, $scope, $filters);
            $response = $exporter->download($report, $definition['columns'], $rows);
            $this->record($request, 'report.exported', $report, $scope->label(), count($rows), $filters->toArray(), 'csv');

            return $response;
        }

        $result = $this->reports->execute($report, $scope, $filters);
        $response = $exporter->download($report, $definition['columns'], $result['rows']);
        $this->record($request, 'report.exported', $report, $scope->label(), count($result['rows']), $filters->toArray(), 'csv');

        return $response;
    }

    public function pdf(ReportFilterRequest $request, string $report, PdfReportExporter $exporter): Response
    {
        $this->authorizeExport($request);
        $scope = $this->scope($request);
        $filters = $request->filters();
        $definition = $this->catalog->get($report);

        if ($this->reports->isDetailReport($report)) {
            $count = $this->reports->countDetail($report, $scope, $filters);
            $exporter->guardCount($count);
            $rows = $this->reports->getDetailRows($report, $scope, $filters);
            $result = [
                'columns' => $definition['columns'],
                'rows' => $rows,
                'summary' => ['row_count' => count($rows)],
            ];
            $response = $exporter->download($report, $definition, $result, ['generated_at' => now(), 'scope' => $scope->label(), 'filters' => $filters->toArray()]);
            $this->record($request, 'report.exported', $report, $scope->label(), count($rows), $filters->toArray(), 'pdf');

            return $response;
        }

        $result = $this->reports->execute($report, $scope, $filters);
        $response = $exporter->download($report, $definition, $result, ['generated_at' => now(), 'scope' => $scope->label(), 'filters' => $filters->toArray()]);
        $this->record($request, 'report.exported', $report, $scope->label(), count($result['rows']), $filters->toArray(), 'pdf');

        return $response;
    }

    private function scope(ReportFilterRequest $request)
    {
        return $this->scopes->execute($request->user(), (string) $request->route()?->getName());
    }

    private function authorizeView(ReportFilterRequest $request): void
    {
        abort_unless(app(ReportPolicy::class)->view($request->user()), 403);
    }

    private function authorizeExport(ReportFilterRequest $request): void
    {
        abort_unless(app(ReportPolicy::class)->export($request->user()), 403);
    }

    /** @param array<string,mixed> $filters */
    private function record(ReportFilterRequest $request, string $event, string $report, string $scope, int $rows, array $filters, ?string $format = null): void
    {
        /** @var User $actor */ $actor = $request->user();
        $this->audit->write($actor, $event, $format ? "Exported {$report} report as {$format}." : "Viewed {$report} report.", AuditRequestContext::fromRequest($request), subjectName: $report, newValues: ['report' => $report, 'format' => $format, 'scope' => $scope, 'row_count' => $rows, 'filters' => $filters]);
    }
}
