<?php

namespace App\Support;

use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;

class PortableSchemaBlueprint extends Blueprint
{
    public function build()
    {
        if ($this->connection->getDriverName() === 'mysql') {
            $uniquelyIndexedColumns = collect($this->getCommands())
                ->filter(fn ($command): bool => ($command->name ?? null) === 'unique')
                ->flatMap(fn ($command): array => (array) ($command->columns ?? []))
                ->all();

            foreach ($this->getColumns() as $column) {
                if (($column->type ?? null) === 'json' && is_string($column->default ?? null)) {
                    $quotedDefault = $this->connection->getPdo()->quote($column->default);
                    $column->default(new Expression("({$quotedDefault})"));
                }

                if (
                    (($column->unique ?? false) || in_array($column->name, $uniquelyIndexedColumns, true))
                    && in_array($column->type ?? null, ['text', 'mediumText', 'longText'], true)
                ) {
                    $column->type = 'string';
                    $column->length = 512;
                }
            }
        }

        parent::build();
    }

    /**
     * MySQL limits identifiers to 64 characters. Laravel's descriptive default
     * names can exceed that limit for compound indexes on domain tables.
     */
    protected function createIndexName($type, array $columns)
    {
        $name = parent::createIndexName($type, $columns);

        if ($this->connection->getDriverName() !== 'mysql' || strlen($name) <= 64) {
            return $name;
        }

        $hash = substr(hash('sha256', $name), 0, 12);

        return substr($name, 0, 51).'_'.$hash;
    }
}
