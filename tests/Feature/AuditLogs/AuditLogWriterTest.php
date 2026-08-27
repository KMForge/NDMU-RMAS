<?php

namespace Tests\Feature\AuditLogs;

use App\Models\AuditLog;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class AuditLogWriterTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_captures_server_actor_snapshots_context_and_safe_changes(): void
    {
        $actor = User::factory()->create(['name' => 'Audit Actor', 'email' => 'actor@ndmu.edu.ph']);
        $subject = User::factory()->create(['name' => 'Audit Subject', 'email' => 'subject@ndmu.edu.ph']);

        $log = app(AuditLogWriter::class)->write(
            actor: $actor,
            event: 'user.access-updated',
            description: '<b>Access changed</b>',
            requestContext: new AuditRequestContext('192.0.2.10', str_repeat('A', 900), 'admin'),
            auditable: $subject,
            subjectName: $subject->name,
            subjectEmail: $subject->email,
            oldValues: ['roles' => ['faculty']],
            newValues: ['roles' => ['thesis-adviser']],
            actorContext: 'administrator',
        );

        $this->assertSame($actor->id, $log->user_id);
        $this->assertSame('Audit Actor', $log->actor_name);
        $this->assertSame('actor@ndmu.edu.ph', $log->actor_email);
        $this->assertSame('Audit Subject', $log->subject_name);
        $this->assertSame('administrator', $log->actor_context);
        $this->assertSame('succeeded', $log->outcome);
        $this->assertSame('Access changed', $log->description);
        $this->assertSame(['roles' => ['faculty']], $log->old_values);
        $this->assertSame(500, mb_strlen((string) $log->user_agent));
    }

    public function test_writer_recursively_redacts_secrets_and_bounds_oversized_payloads(): void
    {
        $actor = User::factory()->create();

        $log = app(AuditLogWriter::class)->write(
            actor: $actor,
            event: 'system-settings.updated',
            description: 'Settings changed.',
            requestContext: AuditRequestContext::none(),
            oldValues: ['password' => 'plain text', 'nested' => ['access_token' => 'token', 'safe' => 'yes']],
            newValues: ['safe' => str_repeat('x', 20000), 'signature_path' => '/private/signature.png'],
        );

        $this->assertSame('[REDACTED]', $log->old_values['password']);
        $this->assertSame('[REDACTED]', $log->old_values['nested']['access_token']);
        $this->assertSame('yes', $log->old_values['nested']['safe']);
        $this->assertSame('[REDACTED]', $log->new_values['signature_path']);
        $this->assertLessThanOrEqual(500, mb_strlen($log->new_values['safe']));
    }

    public function test_nullable_system_actor_must_be_explicitly_allowed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        app(AuditLogWriter::class)->write(
            actor: null,
            event: 'auth.login.failed',
            description: 'Denied.',
            requestContext: AuditRequestContext::none(),
        );
    }

    public function test_model_update_and_delete_are_blocked(): void
    {
        $log = $this->auditLog();

        try {
            $log->update(['description' => 'tampered']);
            $this->fail('Updating an audit log should throw.');
        } catch (LogicException $exception) {
            $this->assertSame('Audit logs are append-only.', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $log->delete();
    }

    public function test_rolled_back_transaction_does_not_leave_a_success_audit(): void
    {
        try {
            DB::transaction(function (): void {
                $this->auditLog();
                throw new RuntimeException('rollback');
            });
        } catch (RuntimeException) {
            // Expected test rollback.
        }

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_actor_and_subject_snapshots_survive_source_record_deletion(): void
    {
        $actor = User::factory()->create(['name' => 'Former Actor']);
        $subject = User::factory()->create(['name' => 'Former Subject']);
        $log = app(AuditLogWriter::class)->write(
            actor: $actor,
            event: 'user.access-updated',
            description: 'Access changed.',
            requestContext: AuditRequestContext::none(),
            auditable: $subject,
            subjectName: $subject->name,
            subjectEmail: $subject->email,
        );

        $subject->delete();
        $actor->delete();

        $log->refresh();
        $this->assertNull($log->user_id);
        $this->assertSame('Former Actor', $log->actor_name);
        $this->assertSame('Former Subject', $log->subject_name);
    }

    private function auditLog(): AuditLog
    {
        $actor = User::factory()->create();

        return app(AuditLogWriter::class)->write(
            actor: $actor,
            event: 'user.created',
            description: 'User created.',
            requestContext: AuditRequestContext::none(),
            auditable: $actor,
            subjectName: $actor->name,
        );
    }
}
