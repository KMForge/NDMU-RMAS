<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'storage_disk',
    'storage_path',
    'original_filename',
    'mime_type',
    'file_size',
    'content_sha256',
    'registered_at',
])]
#[Hidden(['storage_disk', 'storage_path', 'content_sha256'])]
class UserSignature extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'registered_at' => 'immutable_datetime',
        ];
    }
}
