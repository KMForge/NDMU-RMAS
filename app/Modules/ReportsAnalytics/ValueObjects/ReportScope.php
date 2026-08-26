<?php

namespace App\Modules\ReportsAnalytics\ValueObjects;

final readonly class ReportScope
{
    /** @param list<int> $researchClassIds */
    public function __construct(public string $workspace, public int $actorId, public array $researchClassIds) {}

    public function isFacilitator(): bool
    {
        return $this->workspace === 'facilitator';
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode([$this->workspace, $this->actorId, $this->researchClassIds], JSON_THROW_ON_ERROR));
    }

    public function label(): string
    {
        return $this->isFacilitator() ? 'Owned research classes' : 'CEAC-wide';
    }
}
