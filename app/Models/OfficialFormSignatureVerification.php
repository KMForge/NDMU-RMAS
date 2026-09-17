<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'official_form_signature_id',
    'public_reference',
])]
class OfficialFormSignatureVerification extends Model
{
    public function signature(): BelongsTo
    {
        return $this->belongsTo(OfficialFormSignature::class, 'official_form_signature_id');
    }
}
