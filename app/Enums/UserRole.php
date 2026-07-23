<?php

namespace App\Enums;

use App\Models\User;

enum UserRole: string
{
    case StudentResearcher = 'student-researcher';
    case ResearchAdviser = 'research-adviser';
    case Panelist = 'panelist';
    case ResearchFacilitator = 'research-facilitator';
    case CollegeDean = 'college-dean';
    case SystemAdministrator = 'system-administrator';

    public function label(): string
    {
        return match ($this) {
            self::StudentResearcher => 'Student Researcher',
            self::ResearchAdviser => 'Research Adviser',
            self::Panelist => 'Panelist',
            self::ResearchFacilitator => 'Research Facilitator',
            self::CollegeDean => 'College Dean',
            self::SystemAdministrator => 'System Administrator',
        };
    }

    public function dashboardRoute(): string
    {
        return match ($this) {
            self::StudentResearcher => 'student.dashboard',
            self::ResearchAdviser => 'adviser.dashboard',
            self::Panelist => 'panelist.dashboard',
            self::ResearchFacilitator => 'facilitator.dashboard',
            self::CollegeDean => 'dean.dashboard',
            self::SystemAdministrator => 'admin.dashboard',
        };
    }

    public static function highestFor(User $user): ?self
    {
        $priority = [
            self::SystemAdministrator,
            self::CollegeDean,
            self::ResearchFacilitator,
            self::ResearchAdviser,
            self::Panelist,
            self::StudentResearcher,
        ];

        foreach ($priority as $role) {
            if ($user->hasRole($role->value)) {
                return $role;
            }
        }

        return null;
    }
}
