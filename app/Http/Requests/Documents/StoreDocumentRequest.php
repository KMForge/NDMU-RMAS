<?php

namespace App\Http\Requests\Documents;

use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Modules\Documents\Rules\SecureDocumentFile;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;

class StoreDocumentRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        return $this->user()?->can('documents.upload') === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'submission_token' => ['bail', 'required', 'uuid'],
            'document' => [
                'bail',
                'required',
                'file',
                'max:'.config('ndmu-rmas.document.max_upload_kilobytes', 10240),
                new SecureDocumentFile,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'submission_token.required' => 'The upload session is missing. Please select the document again.',
            'submission_token.uuid' => 'The upload session is invalid. Please select the document again.',
            'document.required' => 'Please choose a PDF or DOCX document.',
            'document.file' => 'The selected upload is not a valid file.',
            'document.max' => 'The document must not be larger than 10 MB.',
        ];
    }

    protected function failedAuthorization(): void
    {
        $reason = 'You do not have permission to submit documents.';
        $this->recordFailure($reason);

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $reason], 403));
        }

        throw new HttpResponseException(
            to_route('student.dashboard')
                ->withErrors(['document' => $reason])
                ->with('document_error', $reason),
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        $message = $validator->errors()->first() ?: 'The document upload is invalid.';
        $this->recordFailure($message);

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'The document could not be uploaded.',
                'errors' => $validator->errors(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    private function recordFailure(string $reason): void
    {
        $file = $this->file('document');

        app(RecordDocumentUploadAttempt::class)->failure(
            $this->user(),
            $file instanceof UploadedFile ? $file : null,
            $this->ip(),
            $reason,
        );
    }
}
