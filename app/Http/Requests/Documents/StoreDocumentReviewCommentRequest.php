<?php

namespace App\Http\Requests\Documents;

use App\Models\DocumentReviewComment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDocumentReviewCommentRequest extends FormRequest
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
            'comment' => ['bail', 'required', 'string', 'min:2', 'max:5000'],
            'page_number' => ['bail', 'nullable', 'integer', 'min:1', 'max:10000'],
            'severity' => ['bail', 'required', Rule::in(['comment', 'revision', 'critical'])],
            'parent_id' => [
                'bail',
                'nullable',
                'integer',
                Rule::exists(DocumentReviewComment::class, 'id')->where(
                    'document_id',
                    $this->route('document')?->getKey(),
                ),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $comment = trim(strip_tags((string) $this->input('comment')));

        $this->merge([
            'comment' => $comment,
            'severity' => strtolower(trim((string) $this->input('severity', 'comment'))),
            'page_number' => $this->input('page_number') === '' ? null : $this->input('page_number'),
            'parent_id' => $this->input('parent_id') === '' ? null : $this->input('parent_id'),
        ]);
    }

    protected function failedAuthorization(): void
    {
        $message = 'You do not have permission to comment on this document.';

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('adviser.dashboard', ['tab' => 'docreview'])
                ->withErrors(['document_review' => $message]),
        );
    }
}
