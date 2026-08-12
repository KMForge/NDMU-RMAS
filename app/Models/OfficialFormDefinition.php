<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'code',
    'title',
    'description',
    'default_category',
    'ownership_scope',
    'cardinality',
    'template_view',
    'is_active',
    'sort_order',
    'metadata',
])]
class OfficialFormDefinition extends Model
{
    public function instances(): HasMany
    {
        return $this->hasMany(OfficialFormInstance::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }
}
