<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'defense_id',
    'official_form_instance_id',
    'official_form_version_id',
    'status',
    'approved_title_number',
    'remarks',
    'result_recorded_by',
    'result_recorded_at',
    'presented_at',
    'presentation_completed_by',
    'finalized_at',
    'finalized_by',
])]
class TitlePresentation extends Model
{
    public function defense(): BelongsTo
    {
        return $this->belongsTo(Defense::class);
    }

    public function formInstance(): BelongsTo
    {
        return $this->belongsTo(OfficialFormInstance::class, 'official_form_instance_id');
    }

    public function formVersion(): BelongsTo
    {
        return $this->belongsTo(OfficialFormVersion::class, 'official_form_version_id');
    }

    public function resultRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'result_recorded_by');
    }

    protected function casts(): array
    {
        return [
            'approved_title_number' => 'integer',
            'result_recorded_at' => 'immutable_datetime',
            'presented_at' => 'immutable_datetime',
            'finalized_at' => 'immutable_datetime',
        ];
    }
}
