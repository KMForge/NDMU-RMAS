<?php

namespace App\Modules\OfficialForms\Validators;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class OfficialFormPayloadValidator
{
    /** @var list<string> */
    private const FORBIDDEN_SYSTEM_KEYS = [
        'id', 'definition_id', 'official_form_definition_id', 'research_class_group_id',
        'research_class_id', 'initiated_by', 'assigned_by', 'approved_by', 'certified_by',
        'status', 'current_version_id', 'source_type', 'source_id', 'created_at', 'updated_at',
        'deleted_at', 'actor_user_id', 'user_id', 'signature_id',
    ];

    /**
     * Browser-editable fields verified against the Phase 19 Blade templates.
     * Names of institutional actors, group membership, signatures, decisions,
     * calculated totals, and approval remarks are deliberately server-managed.
     *
     * @var array<string, array<string, string>>
     */
    private const FIELD_SCHEMAS = [
        'RES-026' => ['date' => 'string', 'topics' => 'array'],
        'RES-027' => ['date' => 'string', 'course' => 'string', 'research_title' => 'string'],
        'RES-028' => ['date' => 'string', 'panel_role' => 'string', 'defense' => 'string', 'course' => 'string', 'defense_date' => 'string', 'time' => 'string', 'venue' => 'string', 'research_title' => 'string'],
        'RES-029' => [],
        'RES-030' => ['date' => 'string', 'degree_program' => 'string', 'research_title' => 'string', 'personnel_type' => 'array', 'current_names' => 'array', 'proposed_replacement' => 'string', 'reasons' => 'string'],
        'RES-031' => [],
        'RES-032' => ['date' => 'string', 'consultant_types' => 'array', 'specific_concerns' => 'string', 'recommendations' => 'string', 'follow_up_date' => 'string'],
        'RES-033' => ['date' => 'string', 'defense_type' => 'string', 'defense_date' => 'string', 'time' => 'string'],
        'RES-034' => ['date' => 'string', 'time' => 'string', 'defense_type' => 'string', 'issues' => 'array', 'pages' => 'array'],
        'RES-035' => ['date' => 'string', 'defense_type' => 'string', 'comments' => 'array'],
        'RES-038' => ['date' => 'string', 'day' => 'string', 'month_year' => 'string'],
        'RES-039' => ['revisions' => 'array', 'recommendation' => 'string', 'date' => 'string'],
        'RES-040' => ['date' => 'string'],
        'RES-041' => ['date' => 'string', 'subject_number' => 'string', 'descriptive_title' => 'string', 'entries' => 'array'],
        'RES-042' => ['date' => 'string', 'descriptive_title' => 'string', 'course' => 'string'],
        'RES-043A' => ['problem' => 'string', 'items' => 'array', 'date' => 'string'],
        'RES-043B' => ['ratings' => 'array', 'date' => 'string'],
        'RES-044' => ['date' => 'string', 'salutation' => 'string'],
        'RES-045' => ['date' => 'string'],
        'RES-046' => ['date' => 'string'],
        'RES-047' => ['date' => 'string', 'salutation' => 'string'],
        'RES-048' => ['evaluation_phase' => 'string', 'ratings' => 'array', 'date' => 'string'],
        'RES-049' => ['authorship_confirmed' => 'boolean'],
    ];

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function validate(string $formCode, array $payload): array
    {
        $code = strtoupper($formCode);
        $schema = self::FIELD_SCHEMAS[$code] ?? null;

        if ($schema === null) {
            throw new InvalidArgumentException("Payload validation is not configured for form {$code}.");
        }

        foreach (self::FORBIDDEN_SYSTEM_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                throw new InvalidArgumentException("Payload cannot specify system-managed key [{$key}].");
            }
        }

        $unknown = array_diff(array_keys($payload), array_keys($schema));
        if ($unknown !== []) {
            throw new InvalidArgumentException('Payload contains unknown fields ['.implode(', ', $unknown)."] for form {$code}.");
        }

        $validated = [];
        foreach ($payload as $key => $value) {
            $validated[$key] = match ($schema[$key]) {
                'string' => $this->cleanString($value, $code, $key),
                'array' => $this->cleanArray($value, $code, $key),
                'boolean' => $this->cleanBoolean($value, $code, $key),
                default => throw new InvalidArgumentException("Unsupported validator type for {$code}.{$key}."),
            };
        }

        $this->validateSemantics($code, $validated);

        return $validated;
    }

    private function cleanString(mixed $value, string $code, string $key): string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException("Field [{$key}] for {$code} must be text.");
        }

        $value = trim((string) $value);
        if (mb_strlen($value) > 5000) {
            throw new InvalidArgumentException("Field [{$key}] for {$code} is too long.");
        }

        return $value;
    }

    /** @return array<int|string, mixed> */
    private function cleanArray(mixed $value, string $code, string $key, int $depth = 0): array
    {
        if (! is_array($value)) {
            throw new InvalidArgumentException("Field [{$key}] for {$code} must be an array.");
        }
        if ($depth > 3 || count($value) > 100) {
            throw new InvalidArgumentException("Field [{$key}] for {$code} exceeds the allowed structure.");
        }

        $clean = [];
        foreach ($value as $itemKey => $item) {
            if (is_array($item)) {
                $clean[$itemKey] = $this->cleanArray($item, $code, $key, $depth + 1);
            } elseif (is_string($item) || is_numeric($item) || is_bool($item) || $item === null) {
                $clean[$itemKey] = is_string($item) ? $this->cleanString($item, $code, $key) : $item;
            } else {
                throw new InvalidArgumentException("Field [{$key}] for {$code} contains an unsupported value.");
            }
        }

        return $clean;
    }

    private function cleanBoolean(mixed $value, string $code, string $key): bool
    {
        $validated = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($validated === null) {
            throw new InvalidArgumentException("Field [{$key}] for {$code} must be boolean.");
        }

        return $validated;
    }

    /** @param array<string, mixed> $payload */
    private function validateSemantics(string $code, array $payload): void
    {
        foreach (['date', 'defense_date', 'follow_up_date'] as $dateField) {
            if (! isset($payload[$dateField]) || $payload[$dateField] === '') {
                continue;
            }

            try {
                $parsed = CarbonImmutable::createFromFormat('Y-m-d', (string) $payload[$dateField]);
            } catch (\Throwable) {
                $parsed = null;
            }

            if ($parsed === null || $parsed->format('Y-m-d') !== $payload[$dateField]) {
                throw new InvalidArgumentException("Field [{$dateField}] for {$code} must use a valid Y-m-d date.");
            }
        }

        if ($code === 'RES-026' && isset($payload['topics']) && count($payload['topics']) > 3) {
            throw new InvalidArgumentException('RES-026 permits at most three proposed topics.');
        }

        if ($code === 'RES-041') {
            $this->validateRows($payload['entries'] ?? [], ['title', 'researchers'], $code, 'entries', 8);
        }

        if ($code === 'RES-043A') {
            $this->validateRows($payload['items'] ?? [], ['question', 'decision', 'comment'], $code, 'items', 10);
            foreach ($payload['items'] ?? [] as $item) {
                if (($item['decision'] ?? '') !== '' && ! in_array($item['decision'], ['accept', 'revise', 'reject'], true)) {
                    throw new InvalidArgumentException('RES-043A item decisions must be accept, revise, or reject.');
                }
            }
        }

        if (in_array($code, ['RES-043B', 'RES-048'], true)) {
            foreach ($payload['ratings'] ?? [] as $rating) {
                if (! is_numeric($rating) || (float) $rating < 1 || (float) $rating > 5) {
                    throw new InvalidArgumentException("Ratings for {$code} must be between 1 and 5.");
                }
            }
        }
    }

    /** @param array<int|string, mixed> $rows
     * @param  list<string>  $allowedKeys
     */
    private function validateRows(array $rows, array $allowedKeys, string $code, string $field, int $maximum): void
    {
        if (count($rows) > $maximum) {
            throw new InvalidArgumentException("Field [{$field}] for {$code} permits at most {$maximum} rows.");
        }

        foreach ($rows as $row) {
            if (! is_array($row) || array_diff(array_keys($row), $allowedKeys) !== []) {
                throw new InvalidArgumentException("Field [{$field}] for {$code} contains a malformed row.");
            }
        }
    }
}
