<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'submission_token',
    'original_filename',
    'stored_filename',
    'file_type',
    'mime_type',
    'file_size',
    'storage_disk',
    'storage_path',
    'content_sha256',
    'submitted_at',
    'status',
])]
#[Hidden(['submission_token', 'stored_filename', 'storage_disk', 'storage_path', 'content_sha256'])]
class Document extends Model
{
    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'submitted_at' => 'immutable_datetime',
            'status' => DocumentStatus::class,
        ];
    }
}
