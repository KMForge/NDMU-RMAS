<?php

namespace App\Modules\AuditLogs\ValueObjects;

use Illuminate\Http\Request;

final readonly class AuditRequestContext
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?string $activeWorkspace,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $workspace = $request->hasSession()
            ? $request->session()->get('active_workspace')
            : null;

        return new self(
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
            activeWorkspace: is_string($workspace) ? $workspace : null,
        );
    }

    public static function none(): self
    {
        return new self(null, null, null);
    }
}
