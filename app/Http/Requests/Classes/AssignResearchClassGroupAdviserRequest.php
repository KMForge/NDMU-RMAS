<?php

namespace App\Http\Requests\Classes;

use Illuminate\Foundation\Http\FormRequest;

class AssignResearchClassGroupAdviserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('classes.assign-advisers') === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'adviser_id' => ['bail', 'required', 'integer', 'exists:users,id'],
        ];
    }
}
