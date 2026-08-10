<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StoreDocumentReviewCommentRequest extends FormRequest
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
        $document = $this->route('document');
        $isDocx = $document instanceof Document && strtolower((string) $document->file_type) === 'docx';

        return [
            'comment' => ['bail', 'required', 'string', 'min:2', 'max:5000'],
            'page_number' => $isDocx
                ? ['bail', 'prohibited']
                : ['bail', 'nullable', 'integer', 'min:1', 'max:10000'],
            'severity' => ['bail', 'required', Rule::in(['comment', 'revision', 'critical'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $comment = trim(strip_tags((string) $this->input('comment')));

        $this->merge([
            'comment' => $comment,
            'severity' => strtolower(trim((string) $this->input('severity', 'comment'))),
            'page_number' => $this->input('page_number') === '' || $this->input('page_number') === null ? null : (int) $this->input('page_number'),
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
