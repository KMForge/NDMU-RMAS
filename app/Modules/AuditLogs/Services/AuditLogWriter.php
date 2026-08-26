<?php

namespace App\Modules\AuditLogs\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\AuditLogs\Support\AuditEvent;
use App\Modules\AuditLogs\Support\AuditPayloadSanitizer;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final class AuditLogWriter
{
    public function __construct(private readonly AuditPayloadSanitizer $sanitizer) {}

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function write(
        ?User $actor,
        string $event,
        string $description,
        AuditRequestContext $requestContext,
        ?Model $auditable = null,
        ?string $subjectName = null,
        ?string $subjectEmail = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $actorContext = null,
        string $outcome = 'succeeded',
        bool $allowSystemActor = false,
    ): AuditLog {
        if (! AuditEvent::isAllowed($event)) {
            throw new InvalidArgumentException("Unsupported audit event [{$event}].");
        }

        if ($actor === null && ! $allowSystemActor) {
            throw new InvalidArgumentException('A nullable audit actor must be explicitly authorized as a system event.');
        }

        if (! in_array($outcome, ['succeeded', 'denied', 'failed'], true)) {
            throw new InvalidArgumentException("Unsupported audit outcome [{$outcome}].");
        }

        return AuditLog::query()->create([
            'user_id' => $actor?->getKey(),
            'actor_name' => $actor?->name,
            'actor_email' => $actor?->email,
            'actor_context' => $this->bounded($actorContext ?? $requestContext->activeWorkspace, 64),
            'outcome' => $outcome,
            'subject_name' => $this->bounded($subjectName, 255),
            'subject_email' => $this->bounded($subjectEmail, 255),
            'event' => $event,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'description' => $this->bounded(strip_tags($description), 500),
            'old_values' => $this->sanitizer->sanitize($oldValues),
            'new_values' => $this->sanitizer->sanitize($newValues),
            'ip_address' => $this->bounded($requestContext->ipAddress, 45),
            'user_agent' => $this->bounded($requestContext->userAgent, 500),
            'created_at' => now(),
        ]);
    }

    private function bounded(?string $value, int $length): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr(trim($value), 0, $length);
    }
}
