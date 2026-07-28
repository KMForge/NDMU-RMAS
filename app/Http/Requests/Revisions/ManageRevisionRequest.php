<?php

namespace App\Http\Requests\Revisions;

use App\Models\RevisionRequest;
use Illuminate\Foundation\Http\FormRequest;

class ManageRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $revisionRequest = $this->route('revisionRequest');

        return $revisionRequest instanceof RevisionRequest
            && $this->user()?->can('manage', $revisionRequest) === true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
