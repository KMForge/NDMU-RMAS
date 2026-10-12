<?php

namespace Tests\Unit;

use App\Modules\OfficialForms\Validators\OfficialFormPayloadValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class OfficialFormDateValidationTest extends TestCase
{
    public function test_every_editable_form_requires_valid_submission_dates_but_allows_incomplete_drafts(): void
    {
        $validator = new OfficialFormPayloadValidator;
        // Catalog codes use three digits; RES-043 has two separate templates.
        $codes = array_merge(array_map(fn ($number) => sprintf('RES-%03d', $number), array_diff(range(26, 49), [43])), ['RES-043A', 'RES-043B']);
        foreach ($codes as $code) {
            $this->assertSame([], $validator->validate($code, []));
            $dates = OfficialFormPayloadValidator::requiredSubmissionDates($code);
            $payload = array_fill_keys($dates, '2026-10-12');
            $this->assertSame($payload, $validator->validateForSubmission($code, $payload));
            foreach ($dates as $field) {
                foreach ([null, '', '2026-02-30', '12/10/2026'] as $invalid) {
                    try {
                        $validator->validateForSubmission($code, array_replace($payload, [$field => $invalid]));
                        $this->fail("{$code}.{$field} accepted an invalid date.");
                    } catch (InvalidArgumentException $exception) {
                        $this->assertStringContainsString(str_replace('_', ' ', $field), str_replace('_', ' ', $exception->getMessage()));
                    }
                }
                $missing = $payload;
                unset($missing[$field]);
                $this->expectMissingDate($validator, $code, $missing);
            }
        }
    }

    private function expectMissingDate(OfficialFormPayloadValidator $validator, string $code, array $payload): void
    {
        try {
            $validator->validateForSubmission($code, $payload);
            $this->fail('Missing submission date was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('required', $exception->getMessage());
        }
    }

    public function test_optional_follow_up_and_receipt_dates_remain_optional(): void
    {
        $validator = new OfficialFormPayloadValidator;
        foreach (['RES-032', 'RES-033'] as $code) {
            $this->assertSame(['date' => '2026-10-12'], $validator->validateForSubmission($code, ['date' => '2026-10-12']));
        }
    }
}
