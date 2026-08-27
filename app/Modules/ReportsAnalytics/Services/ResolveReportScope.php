<?php

namespace App\Modules\ReportsAnalytics\Services;

use App\Models\User;
use App\Modules\ReportsAnalytics\ValueObjects\ReportScope;
use Illuminate\Support\Facades\DB;

final class ResolveReportScope
{
    public function execute(User $user, string $routeName): ReportScope
    {
        $workspace = str($routeName)->before('.')->toString();
        abort_unless(in_array($workspace, ['admin', 'dean', 'facilitator'], true), 403);

        $classIds = $workspace === 'facilitator'
            ? DB::table('research_classes')->where('facilitator_id', $user->id)->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        return new ReportScope($workspace, (int) $user->id, $classIds);
    }
}
