<?php

namespace App\Modules\OfficialForms\Actions;

use App\Models\OfficialFormInstance;
use App\Models\ResearchGroupAdviserChangeRequest;
use App\Models\User;
use InvalidArgumentException;

class ReplaceCurrentAdviser
{
    public function handle(OfficialFormInstance $instance, User $incomingAdviser, User $authorizedBy): void
    {
        $code = strtoupper($instance->definition->code ?? '');
        if ($code !== 'RES-030') {
            throw new InvalidArgumentException("ReplaceCurrentAdviser requires RES-030 instance, given {$code}.");
        }

        $changeRequest = ResearchGroupAdviserChangeRequest::query()
            ->where('official_form_instance_id', $instance->id)
            ->where('status', 'submitted')
            ->first();
        if ($changeRequest === null || (int) $changeRequest->requested_adviser_id !== (int) $incomingAdviser->id) {
            throw new InvalidArgumentException('The incoming adviser does not match a submitted RES-030 adviser change request.');
        }

        app(DecideAdviserChangeRequest::class)->handle($authorizedBy, $instance, 'approved');
    }
}
