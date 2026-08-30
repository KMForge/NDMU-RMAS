<?php

namespace App\Modules\OfficialForms\Services;

use App\Enums\AccountStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Collection;

class InstitutionalActorResolver
{
    /** @var Collection<int, User>|null */
    private ?Collection $eligibleDeans = null;

    /**
     * Resolve the single authoritative College Dean for the institution.
     *
     * NDMU-RMAS currently serves one college. Returning null when zero or more
     * than one eligible Dean exists makes academic approval fail closed rather
     * than silently selecting the wrong user.
     */
    public function dean(): ?User
    {
        $candidates = $this->deanCandidates();

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    public function isDean(User $user): bool
    {
        return (int) $this->dean()?->getKey() === (int) $user->getKey();
    }

    public function hasConfiguredDean(): bool
    {
        return $this->deanCandidates()->isNotEmpty();
    }

    /** @return Collection<int, User> */
    private function deanCandidates(): Collection
    {
        return $this->eligibleDeans ??= User::query()
            ->where('user_type', UserType::Faculty)
            ->where('status', AccountStatus::Active)
            ->whereNotNull('approved_at')
            ->whereNotNull('email_verified_at')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['dean', 'college-dean']))
            ->orderBy('id')
            ->limit(2)
            ->get();
    }
}
