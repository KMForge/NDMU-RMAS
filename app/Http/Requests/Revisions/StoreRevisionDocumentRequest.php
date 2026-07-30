<?php

namespace App\Http\Requests\Revisions;

use App\Models\RevisionRequest;
use App\Modules\Documents\Actions\RecordDocumentUploadAttempt;
use App\Modules\Documents\Rules\SecureDocumentFile;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;

class StoreRevisionDocumentRequest extends FormRequest
{
    protected $stopOnFirstFailure = true;

    public function authorize(): bool
    {
        $revisionRequest = $this->route('revisionRequest');

        return $revisionRequest instanceof RevisionRequest
            && $this->user()?->can('submit', $revisionRequest) === true;
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
            'submission_token.required' => 'The revision upload session is missing.',
            'submission_token.uuid' => 'The revision upload session is invalid.',
            'document.required' => 'Please choose a revised PDF or DOCX document.',
            'document.file' => 'The selected revision is not a valid file.',
            'document.max' => 'The revised document must not be larger than 10 MB.',
        ];
    }

    protected function failedAuthorization(): void
    {
        $message = 'You are not allowed to submit this revision.';
        $this->recordFailure($message);

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json(['message' => $message], 403));
        }

        throw new HttpResponseException(
            to_route('student.dashboard', ['tab' => 'revisions'])
                ->withErrors(['revision' => $message]),
        );
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->recordFailure(
            $validator->errors()->first() ?: 'The revised document is invalid.',
        );

        if ($this->expectsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => 'The revised document could not be uploaded.',
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
