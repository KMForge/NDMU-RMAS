<?php

namespace App\Http\Requests\Classes;

use Illuminate\Foundation\Http\FormRequest;

class CreateResearchClassGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('classes.manage-groups') === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'creation_token' => ['bail', 'required', 'uuid'],
            'name' => ['bail', 'required', 'string', 'min:2', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim(strip_tags((string) $this->input('name'))),
        ]);
    }
}
