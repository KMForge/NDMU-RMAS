<?php

namespace App\Modules\ReportsAnalytics\ValueObjects;

final readonly class ReportFilters
{
    public function __construct(
        public ?int $academicYearId = null,
        public ?int $academicTermId = null,
        public ?int $programId = null,
        public ?int $researchClassId = null,
        public ?int $adviserId = null,
        public ?string $stage = null,
        public ?string $status = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}

    /** @param array<string, mixed> $values */
    public static function from(array $values): self
    {
        $integer = fn (string $key): ?int => isset($values[$key]) && $values[$key] !== '' ? (int) $values[$key] : null;

        return new self(
            $integer('academic_year_id'), $integer('academic_term_id'), $integer('program_id'),
            $integer('research_class_id'), $integer('adviser_id'),
            self::text($values['stage'] ?? null), self::text($values['status'] ?? null),
            self::text($values['date_from'] ?? null), self::text($values['date_to'] ?? null),
        );
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return array_filter([
            'academic_year_id' => $this->academicYearId, 'academic_term_id' => $this->academicTermId,
            'program_id' => $this->programId, 'research_class_id' => $this->researchClassId,
            'adviser_id' => $this->adviserId, 'stage' => $this->stage, 'status' => $this->status,
            'date_from' => $this->dateFrom, 'date_to' => $this->dateTo,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function fingerprint(): string
    {
        return hash('sha256', json_encode($this->toArray(), JSON_THROW_ON_ERROR));
    }

    private static function text(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
