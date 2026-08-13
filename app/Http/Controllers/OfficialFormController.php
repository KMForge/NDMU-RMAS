<?php

namespace App\Http\Controllers;

use App\Models\OfficialFormInstance;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfficialFormController extends Controller
{
    use AuthorizesRequests;

    /**
     * Render authoritative printable view for a saved form instance.
     */
    public function print(Request $request, OfficialFormInstance $instance): View
    {
        $this->authorize('view', $instance);

        $instance->load([
            'definition',
            'currentVersion',
            'versions.creator',
            'group.leader',
            'group.adviser',
            'researchClass.facilitator',
            'actorAssignments.user',
        ]);

        $code = strtolower($instance->definition->code);
        $customPrintView = "pages.official-forms.print-{$code}";

        if (view()->exists($customPrintView)) {
            return view($customPrintView, [
                'instance' => $instance,
                'version' => $instance->currentVersion,
                'payload' => $instance->currentVersion?->payload ?? [],
            ]);
        }

        return view('pages.official-forms.print', [
            'instance' => $instance,
            'version' => $instance->currentVersion,
            'payload' => $instance->currentVersion?->payload ?? [],
        ]);
    }
}
