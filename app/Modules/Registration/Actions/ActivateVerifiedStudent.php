<?php

namespace App\Modules\Registration\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;

class ActivateVerifiedStudent
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function handle(User $student, ?AuditRequestContext $requestContext = null): User
    {
        return DB::transaction(function () use ($student, $requestContext): User {
            $student = User::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($student->user_type !== UserType::Student
                || ! $student->hasVerifiedEmail()
                || $student->status !== AccountStatus::Pending) {
                return $student;
            }

            $student->forceFill([
                'status' => AccountStatus::Active,
                'approved_at' => now(),
            ])->save();

            $this->auditLogs->write(
                actor: $student,
                event: 'user.activated',
                description: 'A student account was activated after institutional email verification.',
                requestContext: $requestContext ?? AuditRequestContext::none(),
                auditable: $student,
                subjectName: $student->name,
                subjectEmail: $student->email,
                oldValues: ['status' => AccountStatus::Pending->value],
                newValues: ['status' => AccountStatus::Active->value],
                actorContext: 'student-registration',
            );

            return $student->refresh();
        });
    }
}
