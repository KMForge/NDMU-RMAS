<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'official_form_version_id',
    'public_reference',
])]
class OfficialFormVerification extends Model
{
    public function version(): BelongsTo
    {
        return $this->belongsTo(OfficialFormVersion::class, 'official_form_version_id');
    }
}
