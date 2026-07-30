<?php

namespace App\Http\Requests\Documents;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class ReviewDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('documents.review') === true;
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
        $notes = trim(strip_tags((string) $this->input('review_notes')));

        $this->merge([
            'decision' => strtolower(trim((string) $this->input('decision'))),
            'review_notes' => $notes === '' ? null : $notes,
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to review this document.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'docreview'])
                ->withErrors(['document_review' => $message]),
        );
    }
}
