<?php

namespace App\Models;

use App\Enums\DocumentStage;
use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'research_class_group_id',
    'revision_request_id',
    'submission_token',
    'original_filename',
    'stored_filename',
    'file_type',
    'mime_type',
    'document_stage',
    'version_number',
    'is_current',
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
    public function formattedFileSize(): string
    {
        $size = max(0, (int) $this->file_size);
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        $precision = $unit === 0 ? 0 : 1;

        return number_format($size, $precision).' '.$units[$unit];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function researchClassGroup(): BelongsTo
    {
        return $this->belongsTo(ResearchClassGroup::class);
    }

    public function revisionRequest(): BelongsTo
    {
        return $this->belongsTo(RevisionRequest::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentReviewComment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DocumentReview::class);
    }

    public function reviewAudits(): HasMany
    {
        return $this->hasMany(DocumentReviewAudit::class);
    }

    public function revisionRequests(): HasMany
    {
        return $this->hasMany(RevisionRequest::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(ResearchProposal::class);
    }

    public function progressUpdates(): HasMany
    {
        return $this->hasMany(ResearchProgressUpdate::class, 'evidence_document_id');
    }

    public function stageLabel(): string
    {
        return $this->document_stage?->label() ?? 'Unclassified';
    }

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'version_number' => 'integer',
            'is_current' => 'boolean',
            'submitted_at' => 'immutable_datetime',
            'document_stage' => DocumentStage::class,
            'status' => DocumentStatus::class,
        ];
    }
}
