<?php

namespace App\Modules\UserManagement\Actions;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\FacultyProfile;
use App\Models\User;
use App\Modules\AuditLogs\Services\AuditLogWriter;
use App\Modules\AuditLogs\ValueObjects\AuditRequestContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageUserAccount
{
    public function __construct(
        private readonly AuditLogWriter $auditLogs,
        private readonly AutoReplaceUnavailableAcademicStaff $autoReplaceUnavailableStaff,
    ) {}

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
     * @param  array{name: string, email: string, department: string, department_id?: int|null, department_ids?: list<int>}  $attributes
     * @return array{user: User, temporary_password: string}
     */
    public function createStaff(array $attributes, User $actor): array
    {
        $temporaryPassword = $this->temporaryPassword();

        return DB::transaction(function () use ($attributes, $actor, $temporaryPassword): array {
            $user = User::query()->create([
                'name' => trim(strip_tags($attributes['name'])),
                'email' => mb_strtolower(trim($attributes['email'])),
                'password' => $temporaryPassword,
                'status' => AccountStatus::Active,
                'approved_at' => now(),
                'email_verified_at' => now(),
                'user_type' => UserType::Faculty,
                'department' => $attributes['department'],
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours($this->temporaryPasswordLifetimeHours()),
            ]);

            $departmentId = $attributes['department_id'] ?? null;
            if ($departmentId !== null && Schema::hasTable('faculty_profiles')) {
                $profile = FacultyProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'department_id' => $departmentId,
                        'employee_number' => 'EMP-'.str_pad((string) $user->id, 5, '0', STR_PAD_LEFT),
                        'specialization' => $attributes['department'],
                    ],
                );

                if (Schema::hasTable('faculty_profile_departments')) {
                    $departmentIds = collect($attributes['department_ids'] ?? [])
                        ->map(fn ($id): int => (int) $id)
                        ->push((int) $departmentId)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();
                    $profile->departments()->sync($departmentIds);
                }
            }

            $this->audit($actor, $user, 'user.created', null, [
                'status' => AccountStatus::Active->value,
                'user_type' => UserType::Faculty->value,
                'roles' => [],
            ]);

            return [
                'user' => $user,
                'temporary_password' => $temporaryPassword,
            ];
        });
    }

    public function issueTemporaryPassword(User $user, User $actor): string
    {
        if ($user->is($actor)) {
            throw ValidationException::withMessages([
                'account' => 'Use your own password settings to change the current administrator password.',
            ]);
        }

        $temporaryPassword = $this->temporaryPassword();

        DB::transaction(function () use ($user, $actor, $temporaryPassword): void {
            $user->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'temporary_password_expires_at' => now()->addHours($this->temporaryPasswordLifetimeHours()),
                'password_changed_at' => null,
                'remember_token' => Str::random(60),
            ])->save();

            $this->audit($actor, $user, 'user.temporary-password.issued', null, [
                'must_change_password' => true,
                'temporary_password_expires_at' => $user->temporary_password_expires_at?->toIso8601String(),
            ]);
        });

        return $temporaryPassword;
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

            if ($status === AccountStatus::Suspended && $user->user_type === UserType::Faculty) {
                $this->autoReplaceUnavailableStaff->handle(
                    unavailable: $user,
                    actor: $actor,
                    reason: 'Faculty account suspended or marked unavailable by an administrator.',
                );
            }

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

    private function temporaryPassword(): string
    {
        return Str::password(length: 16, letters: true, numbers: true, symbols: true, spaces: false);
    }

    private function temporaryPasswordLifetimeHours(): int
    {
        return max(1, (int) config('auth.temporary_password.expire_hours', 72));
    }
}
