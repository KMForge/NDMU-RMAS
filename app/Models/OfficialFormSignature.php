<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'official_form_instance_id',
    'official_form_version_id',
    'signer_user_id',
    'user_signature_id',
    'actor_type',
    'academic_action',
    'signer_name_snapshot',
    'signer_email_snapshot',
    'signature_storage_disk',
    'signature_storage_path',
    'signature_sha256',
    'version_payload_sha256',
    'attestation_hash',
    'attestation_key_version',
    'signed_at',
    'ip_address',
    'user_agent',
])]
class OfficialFormSignature extends Model
{
    public function instance(): BelongsTo
    {
        return $this->belongsTo(OfficialFormInstance::class, 'official_form_instance_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(OfficialFormVersion::class, 'official_form_version_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    public function specimen(): BelongsTo
    {
        return $this->belongsTo(UserSignature::class, 'user_signature_id');
    }

    public function verification(): HasOne
    {
        return $this->hasOne(OfficialFormSignatureVerification::class, 'official_form_signature_id');
    }

    protected function casts(): array
    {
        return [
            'signed_at' => 'immutable_datetime',
        ];
    }
}
