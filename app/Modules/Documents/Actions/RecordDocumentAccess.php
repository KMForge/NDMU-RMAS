<?php

namespace App\Modules\Documents\Actions;

use App\Models\Document;
use App\Models\DocumentAccessAudit;
use App\Models\User;

class RecordDocumentAccess
{
    public function handle(
        Document $document,
        User $user,
        string $action,
        ?string $ipAddress,
        ?string $userAgent,
    ): void {
        DocumentAccessAudit::query()->create([
            'document_id' => $document->getKey(),
            'user_id' => $user->getKey(),
            'action' => $action,
            'ip_address' => filter_var($ipAddress, FILTER_VALIDATE_IP) ? $ipAddress : null,
            'user_agent' => mb_substr((string) $userAgent, 0, 1000),
            'accessed_at' => now(),
        ]);
    }
}
