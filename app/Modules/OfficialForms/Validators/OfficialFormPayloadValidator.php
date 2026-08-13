<?php

namespace App\Modules\OfficialForms\Validators;

use InvalidArgumentException;

class OfficialFormPayloadValidator
{
    /** @var list<string> */
    private const FORBIDDEN_SYSTEM_KEYS = [
        'id',
        'definition_id',
        'official_form_definition_id',
        'research_class_group_id',
        'research_class_id',
        'initiated_by',
        'assigned_by',
        'approved_by',
        'certified_by',
        'status',
        'current_version_id',
        'source_type',
        'source_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Validate payload against official whitelist per form code.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validate(string $formCode, array $payload): array
    {
        $code = strtoupper($formCode);

        // Disallow systemic/meta injection
        foreach (self::FORBIDDEN_SYSTEM_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                throw new InvalidArgumentException("Payload cannot specify system-managed key [{$key}].");
            }
        }

        return match ($code) {
            'RES-026' => $this->validateRes026($payload),
            'RES-027' => $this->validateRes027($payload),
            'RES-028' => $this->validateRes028($payload),
            'RES-029' => $this->validateRes029($payload),
            'RES-030' => $this->validateRes030($payload),
            'RES-031' => $this->validateRes031($payload),
            'RES-032' => $this->validateRes032($payload),
            'RES-033' => $this->validateRes033($payload),
            'RES-034' => $this->validateRes034($payload),
            'RES-035' => $this->validateRes035($payload),
            'RES-038' => $this->validateRes038($payload),
            'RES-039' => $this->validateRes039($payload),
            'RES-040' => $this->validateRes040($payload),
            'RES-041' => $this->validateRes041($payload),
            'RES-042' => $this->validateRes042($payload),
            'RES-043A' => $this->validateRes043a($payload),
            'RES-043B' => $this->validateRes043b($payload),
            'RES-044' => $this->validateRes044($payload),
            'RES-045' => $this->validateRes045($payload),
            'RES-046' => $this->validateRes046($payload),
            'RES-047' => $this->validateRes047($payload),
            'RES-048' => $this->validateRes048($payload),
            'RES-049' => $this->validateRes049($payload),
            default => throw new InvalidArgumentException("Payload validation is not configured for form {$code}."),
        };
    }

    private function validateRes026(array $payload): array
    {
        $allowed = ['date', 'students', 'topics', 'approved_title_number', 'approved_title', 'remarks', 'panel_names'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-026');

        return [
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
            'students' => isset($payload['students']) && is_array($payload['students'])
                ? array_map('strval', array_slice($payload['students'], 0, 4))
                : [],
            'topics' => isset($payload['topics']) && is_array($payload['topics'])
                ? array_map('strval', array_slice($payload['topics'], 0, 3))
                : [],
            'approved_title_number' => isset($payload['approved_title_number']) ? (int) $payload['approved_title_number'] : null,
            'approved_title' => isset($payload['approved_title']) ? (string) $payload['approved_title'] : null,
            'remarks' => isset($payload['remarks']) ? (string) $payload['remarks'] : null,
            'panel_names' => isset($payload['panel_names']) && is_array($payload['panel_names'])
                ? array_map('strval', $payload['panel_names'])
                : [],
        ];
    }

    private function validateRes027(array $payload): array
    {
        $allowed = ['invitation_date', 'adviser_name', 'proposed_title', 'response', 'remarks'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-027');

        return [
            'invitation_date' => isset($payload['invitation_date']) ? (string) $payload['invitation_date'] : null,
            'adviser_name' => isset($payload['adviser_name']) ? (string) $payload['adviser_name'] : null,
            'proposed_title' => isset($payload['proposed_title']) ? (string) $payload['proposed_title'] : null,
            'response' => isset($payload['response']) ? (string) $payload['response'] : null,
            'remarks' => isset($payload['remarks']) ? (string) $payload['remarks'] : null,
        ];
    }

    private function validateRes028(array $payload): array
    {
        $allowed = ['invitation_date', 'panelist_name', 'proposed_title', 'response', 'remarks'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-028');

        return [
            'invitation_date' => isset($payload['invitation_date']) ? (string) $payload['invitation_date'] : null,
            'panelist_name' => isset($payload['panelist_name']) ? (string) $payload['panelist_name'] : null,
            'proposed_title' => isset($payload['proposed_title']) ? (string) $payload['proposed_title'] : null,
            'response' => isset($payload['response']) ? (string) $payload['response'] : null,
            'remarks' => isset($payload['remarks']) ? (string) $payload['remarks'] : null,
        ];
    }

    private function validateRes029(array $payload): array
    {
        $allowed = ['invitation_date', 'editor_name', 'proposed_title', 'response', 'remarks'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-029');

        return [
            'invitation_date' => isset($payload['invitation_date']) ? (string) $payload['invitation_date'] : null,
            'editor_name' => isset($payload['editor_name']) ? (string) $payload['editor_name'] : null,
            'proposed_title' => isset($payload['proposed_title']) ? (string) $payload['proposed_title'] : null,
            'response' => isset($payload['response']) ? (string) $payload['response'] : null,
            'remarks' => isset($payload['remarks']) ? (string) $payload['remarks'] : null,
        ];
    }

    private function validateRes030(array $payload): array
    {
        $allowed = ['reason', 'target_role', 'previous_person_name', 'new_person_name', 'approval_notes'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-030');

        return [
            'reason' => isset($payload['reason']) ? (string) $payload['reason'] : null,
            'target_role' => isset($payload['target_role']) ? (string) $payload['target_role'] : null,
            'previous_person_name' => isset($payload['previous_person_name']) ? (string) $payload['previous_person_name'] : null,
            'new_person_name' => isset($payload['new_person_name']) ? (string) $payload['new_person_name'] : null,
            'approval_notes' => isset($payload['approval_notes']) ? (string) $payload['approval_notes'] : null,
        ];
    }

    private function validateRes031(array $payload): array
    {
        $allowed = ['consultations', 'remarks', 'adviser_notes'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-031');

        return [
            'consultations' => isset($payload['consultations']) && is_array($payload['consultations'])
                ? array_slice($payload['consultations'], 0, 10)
                : [],
            'remarks' => isset($payload['remarks']) ? (string) $payload['remarks'] : null,
            'adviser_notes' => isset($payload['adviser_notes']) ? (string) $payload['adviser_notes'] : null,
        ];
    }

    private function validateRes032(array $payload): array
    {
        $allowed = ['consultant_name', 'specialization', 'topics_discussed', 'recommendations', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-032');

        return [
            'consultant_name' => isset($payload['consultant_name']) ? (string) $payload['consultant_name'] : null,
            'specialization' => isset($payload['specialization']) ? (string) $payload['specialization'] : null,
            'topics_discussed' => isset($payload['topics_discussed']) ? (string) $payload['topics_discussed'] : null,
            'recommendations' => isset($payload['recommendations']) ? (string) $payload['recommendations'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes033(array $payload): array
    {
        $allowed = ['defense_type', 'manuscript_title', 'readiness_notes', 'endorsement_date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-033');

        return [
            'defense_type' => isset($payload['defense_type']) ? (string) $payload['defense_type'] : 'proposal_defense',
            'manuscript_title' => isset($payload['manuscript_title']) ? (string) $payload['manuscript_title'] : null,
            'readiness_notes' => isset($payload['readiness_notes']) ? (string) $payload['readiness_notes'] : null,
            'endorsement_date' => isset($payload['endorsement_date']) ? (string) $payload['endorsement_date'] : null,
        ];
    }

    private function validateRes034(array $payload): array
    {
        $allowed = ['issues', 'pages', 'general_comments', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-034');

        return [
            'issues' => isset($payload['issues']) && is_array($payload['issues']) ? $payload['issues'] : [],
            'pages' => isset($payload['pages']) && is_array($payload['pages']) ? $payload['pages'] : [],
            'general_comments' => isset($payload['general_comments']) ? (string) $payload['general_comments'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes035(array $payload): array
    {
        $allowed = ['proceedings_notes', 'verdict', 'conditions', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-035');

        return [
            'proceedings_notes' => isset($payload['proceedings_notes']) ? (string) $payload['proceedings_notes'] : null,
            'verdict' => isset($payload['verdict']) ? (string) $payload['verdict'] : null,
            'conditions' => isset($payload['conditions']) ? (string) $payload['conditions'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes038(array $payload): array
    {
        $allowed = ['defense_type', 'class_name', 'eligible_groups', 'notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-038');

        return [
            'defense_type' => isset($payload['defense_type']) ? (string) $payload['defense_type'] : null,
            'class_name' => isset($payload['class_name']) ? (string) $payload['class_name'] : null,
            'eligible_groups' => isset($payload['eligible_groups']) && is_array($payload['eligible_groups']) ? $payload['eligible_groups'] : [],
            'notes' => isset($payload['notes']) ? (string) $payload['notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes039(array $payload): array
    {
        $allowed = ['revisions', 'general_notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-039');

        return [
            'revisions' => isset($payload['revisions']) && is_array($payload['revisions']) ? $payload['revisions'] : [],
            'general_notes' => isset($payload['general_notes']) ? (string) $payload['general_notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes040(array $payload): array
    {
        $allowed = ['title', 'adviser_notes', 'instructor_notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-040');

        return [
            'title' => isset($payload['title']) ? (string) $payload['title'] : null,
            'adviser_notes' => isset($payload['adviser_notes']) ? (string) $payload['adviser_notes'] : null,
            'instructor_notes' => isset($payload['instructor_notes']) ? (string) $payload['instructor_notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes041(array $payload): array
    {
        $allowed = ['entries', 'instructor_notes', 'coordinator_notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-041');

        return [
            'entries' => isset($payload['entries']) && is_array($payload['entries']) ? $payload['entries'] : [],
            'instructor_notes' => isset($payload['instructor_notes']) ? (string) $payload['instructor_notes'] : null,
            'coordinator_notes' => isset($payload['coordinator_notes']) ? (string) $payload['coordinator_notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes042(array $payload): array
    {
        $allowed = ['instrument_title', 'target_validator_name', 'request_letter', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-042');

        return [
            'instrument_title' => isset($payload['instrument_title']) ? (string) $payload['instrument_title'] : null,
            'target_validator_name' => isset($payload['target_validator_name']) ? (string) $payload['target_validator_name'] : null,
            'request_letter' => isset($payload['request_letter']) ? (string) $payload['request_letter'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes043a(array $payload): array
    {
        $allowed = ['items', 'overall_remarks', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-043A');

        return [
            'items' => isset($payload['items']) && is_array($payload['items']) ? $payload['items'] : [],
            'overall_remarks' => isset($payload['overall_remarks']) ? (string) $payload['overall_remarks'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes043b(array $payload): array
    {
        $allowed = ['ratings', 'mean', 'verbal_description', 'comments', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-043B');

        return [
            'ratings' => isset($payload['ratings']) && is_array($payload['ratings']) ? $payload['ratings'] : [],
            'mean' => isset($payload['mean']) ? (float) $payload['mean'] : null,
            'verbal_description' => isset($payload['verbal_description']) ? (string) $payload['verbal_description'] : null,
            'comments' => isset($payload['comments']) ? (string) $payload['comments'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes044(array $payload): array
    {
        $allowed = ['title', 'target_agency', 'data_gathering_notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-044');

        return [
            'title' => isset($payload['title']) ? (string) $payload['title'] : null,
            'target_agency' => isset($payload['target_agency']) ? (string) $payload['target_agency'] : null,
            'data_gathering_notes' => isset($payload['data_gathering_notes']) ? (string) $payload['data_gathering_notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes045(array $payload): array
    {
        $allowed = ['manuscript_title', 'editing_notes', 'editor_certification_statement', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-045');

        return [
            'manuscript_title' => isset($payload['manuscript_title']) ? (string) $payload['manuscript_title'] : null,
            'editing_notes' => isset($payload['editing_notes']) ? (string) $payload['editing_notes'] : null,
            'editor_certification_statement' => isset($payload['editor_certification_statement']) ? (string) $payload['editor_certification_statement'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes046(array $payload): array
    {
        $allowed = ['manuscript_title', 'technical_notes', 'editor_certification_statement', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-046');

        return [
            'manuscript_title' => isset($payload['manuscript_title']) ? (string) $payload['manuscript_title'] : null,
            'technical_notes' => isset($payload['technical_notes']) ? (string) $payload['technical_notes'] : null,
            'editor_certification_statement' => isset($payload['editor_certification_statement']) ? (string) $payload['editor_certification_statement'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes047(array $payload): array
    {
        $allowed = ['manuscript_title', 'copies_authorized', 'notes', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-047');

        return [
            'manuscript_title' => isset($payload['manuscript_title']) ? (string) $payload['manuscript_title'] : null,
            'copies_authorized' => isset($payload['copies_authorized']) ? (int) $payload['copies_authorized'] : 5,
            'notes' => isset($payload['notes']) ? (string) $payload['notes'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes048(array $payload): array
    {
        $allowed = ['member_names', 'ratings', 'total_scores', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-048');

        return [
            'member_names' => isset($payload['member_names']) && is_array($payload['member_names']) ? $payload['member_names'] : [],
            'ratings' => isset($payload['ratings']) && is_array($payload['ratings']) ? $payload['ratings'] : [],
            'total_scores' => isset($payload['total_scores']) && is_array($payload['total_scores']) ? $payload['total_scores'] : [],
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    private function validateRes049(array $payload): array
    {
        $allowed = ['manuscript_title', 'author_names', 'authorship_statement', 'date'];
        $this->ensureOnlyAllowedKeys($payload, $allowed, 'RES-049');

        return [
            'manuscript_title' => isset($payload['manuscript_title']) ? (string) $payload['manuscript_title'] : null,
            'author_names' => isset($payload['author_names']) && is_array($payload['author_names']) ? $payload['author_names'] : [],
            'authorship_statement' => isset($payload['authorship_statement']) ? (string) $payload['authorship_statement'] : null,
            'date' => isset($payload['date']) ? (string) $payload['date'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $allowedKeys
     */
    private function ensureOnlyAllowedKeys(array $payload, array $allowedKeys, string $formCode): void
    {
        $unknownKeys = array_diff(array_keys($payload), $allowedKeys);
        if (! empty($unknownKeys)) {
            $keyStr = implode(', ', $unknownKeys);
            throw new InvalidArgumentException("Payload contains unknown fields [{$keyStr}] for form {$formCode}.");
        }
    }
}
