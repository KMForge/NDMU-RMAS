<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class CorrectDocumentReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof Document
            && $this->user()?->can('review', $document) === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'decision' => [
                'bail',
                'required',
                Rule::in(['accepted', 'revision_requested', 'rejected']),
            ],
            'correction_reason' => [
                'bail',
                'required',
                'string',
                'min:5',
                'max:5000',
            ],
            'review_notes' => [
                'bail',
                Rule::requiredIf(
                    fn (): bool => in_array(
                        $this->input('decision'),
                        ['revision_requested', 'rejected'],
                        true,
                    ),
                ),
                'nullable',
                'string',
                'min:5',
                'max:10000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $reason = trim(strip_tags((string) $this->input('correction_reason')));
        $notes = trim(strip_tags((string) $this->input('review_notes')));

        $this->merge([
            'decision' => strtolower(trim((string) $this->input('decision'))),
            'correction_reason' => $reason,
            'review_notes' => $notes === '' ? null : $notes,
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to correct decisions for this document.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'docreview'])
                ->withErrors(['document_review' => $message]),
        );
    }
}
