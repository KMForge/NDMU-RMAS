<?php

namespace App\Http\Requests\Signatures;

use App\Models\UserSignature;
use App\Modules\Signatures\Rules\SecureSignatureImage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class StoreUserSignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', UserSignature::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'signature' => [
                'required',
                'file',
                'min:1',
                'max:'.config('ndmu-rmas.signature.max_upload_kilobytes', 2048),
                'extensions:png,jpg,jpeg',
                'mimetypes:image/png,image/jpeg',
                new SecureSignatureImage,
            ],
        ];
    }
}
