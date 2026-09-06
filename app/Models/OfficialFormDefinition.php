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

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if (is_numeric($value)) {
            return $this->where($field ?? 'id', (int) $value)->firstOrFail();
        }

        $code = strtoupper(str_replace('_', '-', (string) $value));

        return $this->where('code', $code)->firstOrFail();
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
