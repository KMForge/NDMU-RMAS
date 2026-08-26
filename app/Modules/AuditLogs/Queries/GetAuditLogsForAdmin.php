<?php

namespace App\Modules\AuditLogs\Queries;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class GetAuditLogsForAdmin
{
    /** @return array{auditLogs: LengthAwarePaginator, auditLogEvents: Collection<int, string>, auditLogContexts: Collection<int, string>, auditLogStats: array<string, int>} */
    public function get(User $viewer, string $search, string $event, string $context, string $outcome, string $dateFrom, string $dateTo): array
    {
        abort_unless($viewer->can('audit-logs.view'), 403);

        $search = trim(mb_substr(strip_tags($search), 0, 100));
        $event = trim(mb_substr(strip_tags($event), 0, 120));
        $context = trim(mb_substr(strip_tags($context), 0, 64));
        $requestedOutcome = trim(mb_substr(strip_tags($outcome), 0, 16));
        $validOutcome = $requestedOutcome === '' || in_array($requestedOutcome, ['succeeded', 'denied', 'failed'], true);
        $outcome = $validOutcome ? $requestedOutcome : '';
        $from = $this->date($dateFrom);
        $to = $this->date($dateTo);
        $knownEvent = $event !== '' && AuditLog::query()->where('event', $event)->exists();
        $knownContext = $context !== '' && AuditLog::query()->where('actor_context', $context)->exists();

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = addcslashes($search, '\\%_');
                $query->where(function ($query) use ($escaped): void {
                    foreach (['actor_name', 'actor_email', 'subject_name', 'subject_email', 'event', 'description', 'ip_address'] as $column) {
                        $query->orWhere($column, 'like', "%{$escaped}%");
                    }
                });
            })
            ->when($event !== '' && ! $knownEvent, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($knownEvent, fn ($query) => $query->where('event', $event))
            ->when($context !== '' && ! $knownContext, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($knownContext, fn ($query) => $query->where('actor_context', $context))
            ->when(! $validOutcome, fn ($query) => $query->whereRaw('1 = 0'))
            ->when($outcome !== '', fn ($query) => $query->where('outcome', $outcome))
            ->when($from !== null, fn ($query) => $query->where('created_at', '>=', $from->startOfDay()))
            ->when($to !== null, fn ($query) => $query->where('created_at', '<=', $to->endOfDay()))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20, ['*'], 'auditPage');

        return [
            'auditLogs' => $logs,
            'auditLogEvents' => AuditLog::query()->distinct()->orderBy('event')->pluck('event'),
            'auditLogContexts' => AuditLog::query()->whereNotNull('actor_context')->distinct()->orderBy('actor_context')->pluck('actor_context'),
            'auditLogStats' => [
                'today' => AuditLog::query()->where('created_at', '>=', now()->startOfDay())->count(),
                'workspace_switches' => AuditLog::query()->where('event', 'workspace.switched')->count(),
                'access_changes' => AuditLog::query()->whereIn('event', ['user.access-updated', 'role.created', 'role.updated', 'role.deleted'])->count(),
            ],
        ];
    }

    private function date(string $value): ?Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
