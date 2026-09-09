<?php

namespace App\Modules\UserManagement\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageUserAccount
{
    public function __construct(private readonly AuditLogWriter $auditLogs) {}

    public function activate(User $user, User $actor): User
    {
        return $this->changeStatus($user, $actor, AccountStatus::Active, 'user.activated');
    }

    public function suspend(User $user, User $actor): User
    {
        if ($user->can('roles.manage') && User::permission('roles.manage')
            ->where('status', AccountStatus::Active)
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'account' => 'The last active administrator cannot be suspended.',
            ]);
        }

        return $this->changeStatus($user, $actor, AccountStatus::Suspended, 'user.suspended');
    }

    /**
     * @param  array{name: string, email: string, password: string, department: string}  $attributes
     */
    public function createStaff(array $attributes, User $actor): User
    {
        return DB::transaction(function () use ($attributes, $actor): User {
            $user = User::query()->create([
                'name' => trim(strip_tags($attributes['name'])),
                'email' => mb_strtolower(trim($attributes['email'])),
                'password' => $attributes['password'],
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'user_type' => UserType::Faculty,
                'department' => $attributes['department'],
            ]);

            $this->audit($actor, $user, 'user.created', null, [
                'status' => AccountStatus::Active->value,
                'user_type' => UserType::Faculty->value,
                'roles' => [],
            ]);

            return $user;
        });
    }

    private function changeStatus(User $user, User $actor, AccountStatus $status, string $event): User
    {
        return DB::transaction(function () use ($user, $actor, $status, $event): User {
            $previousStatus = $user->status instanceof AccountStatus
                ? $user->status->value
                : (string) $user->status;

            $user->forceFill([
                'status' => $status,
                'approved_at' => $status === AccountStatus::Active ? ($user->approved_at ?? now()) : null,
            ])->save();

            $this->audit($actor, $user, $event, ['status' => $previousStatus], [
                'status' => $status->value,
            ]);

            return $user->refresh();
        });
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function audit(User $actor, User $subject, string $event, ?array $oldValues, array $newValues): void
    {
        $this->auditLogs->write(
            actor: $actor,
            event: $event,
            description: 'User account administration action completed.',
            requestContext: AuditRequestContext::fromRequest(request()),
            auditable: $subject,
            subjectName: $subject->name,
            subjectEmail: $subject->email,
            oldValues: $oldValues,
            newValues: $newValues,
            actorContext: 'administrator',
        );
    }
}
