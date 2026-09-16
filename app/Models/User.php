<?php

namespace App\Models;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Notifications\SendNDMUEmailVerification;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'first_name', 'middle_name', 'last_name', 'suffix', 'email', 'password', 'status', 'approved_at', 'email_verified_at', 'user_type', 'student_id', 'program', 'year_level', 'department', 'profile_photo_disk', 'profile_photo_path', 'profile_photo_mime_type', 'profile_photo_size', 'profile_photo_updated_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public function isActiveAndApproved(): bool
    {
        return $this->status === AccountStatus::Active && $this->approved_at !== null;
    }

    public function isEligibleForSignatureEnrollment(): bool
    {
        return $this->isActiveAndApproved()
            && $this->email_verified_at !== null
            && in_array($this->user_type, [UserType::Student, UserType::Faculty], true);
    }

    public function displayFirstName(): string
    {
        if (! empty($this->first_name)) {
            return $this->first_name;
        }

        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $honorifics = ['atty', 'dr', 'engr', 'fr', 'mr', 'mrs', 'ms', 'prof', 'sr', 'sra'];

        while (count($parts) > 1 && in_array(mb_strtolower(rtrim($parts[0], '.')), $honorifics, true)) {
            array_shift($parts);
        }

        return $parts[0] ?? 'User';
    }

    public function onboardingCompletions(): HasMany
    {
        return $this->hasMany(UserOnboardingCompletion::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function signature(): HasOne
    {
        return $this->hasOne(UserSignature::class);
    }

    public function facultyProfile(): HasOne
    {
        return $this->hasOne(FacultyProfile::class);
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new SendNDMUEmailVerification);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'profile_photo_updated_at' => 'datetime',
            'password' => 'hashed',
            'status' => AccountStatus::class,
            'user_type' => UserType::class,
        ];
    }
}
