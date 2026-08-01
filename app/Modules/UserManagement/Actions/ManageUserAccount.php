<?php

namespace App\Modules\UserManagement\Actions;

use App\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class ManageUserAccount
{
    public function approveStudent(User $student, User $actor): User
    {
        if (! $student->hasRole('student-researcher')) {
            throw ValidationException::withMessages([
                'account' => 'Only student researcher accounts can be approved here.',
            ]);
        }

        $this->ensurePendingStudent($student);

        return $this->changeStatus($student, $actor, AccountStatus::Active, 'user.approved');
    }

    public function rejectStudent(User $student, User $actor): User
    {
        if (! $student->hasRole('student-researcher')) {
            throw ValidationException::withMessages([
                'account' => 'Only student researcher accounts can be rejected here.',
            ]);
        }

        $this->ensurePendingStudent($student);

        return $this->changeStatus($student, $actor, AccountStatus::Rejected, 'user.rejected');
    }

    public function activate(User $user, User $actor): User
    {
        return $this->changeStatus($user, $actor, AccountStatus::Active, 'user.activated');
    }

    public function suspend(User $user, User $actor): User
    {
        if ($user->hasRole('system-administrator') && User::role('system-administrator')
            ->where('status', AccountStatus::Active)
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'account' => 'The last active administrator cannot be suspended.',
            ]);
        }

        return $this->changeStatus($user, $actor, AccountStatus::Suspended, 'user.suspended');
    }

    /**
     * @param  array{name: string, email: string, password: string, department: string, role: string}  $attributes
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
                'department' => $attributes['department'],
            ]);

            $user->assignRole($attributes['role']);
            $this->audit($actor, $user, 'user.created', null, [
                'status' => AccountStatus::Active->value,
                'role' => $attributes['role'],
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

    private function ensurePendingStudent(User $student): void
    {
        if ($student->status !== AccountStatus::Pending) {
            throw ValidationException::withMessages([
                'account' => 'This student registration has already been processed.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function audit(User $actor, User $subject, string $event, ?array $oldValues, array $newValues): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'user_id' => $actor->getKey(),
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $subject->getKey(),
            'description' => "User account action performed for {$subject->email}.",
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($newValues, JSON_THROW_ON_ERROR),
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
