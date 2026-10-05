<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

trait CachesDatabaseSchema
{
    /** @var array<string, true>|null */
    private ?array $knownDatabaseTables = null;

    /** @var array<string, array<string, true>> */
    private array $knownDatabaseColumns = [];

    protected function tableExists(string $table): bool
    {
        if ($this->knownDatabaseTables === null) {
            $this->knownDatabaseTables = Cache::remember(
                $this->schemaCacheKey('tables'),
                now()->addMinutes(5),
                fn (): array => array_fill_keys(
                    array_map('strtolower', Schema::getTableListing(schemaQualified: false)),
                    true,
                ),
            );
        }

        return isset($this->knownDatabaseTables[strtolower($table)]);
    }

    /**
     * @param  array<int, string>  $tables
     */
    protected function tablesExist(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! $this->tableExists($table)) {
                return false;
            }
        }

        return true;
    }

    protected function columnExists(string $table, string $column): bool
    {
        return $this->columnsExist($table, [$column]);
    }

    /**
     * @param  array<int, string>  $columns
     */
    protected function columnsExist(string $table, array $columns): bool
    {
        $key = strtolower($table);

        if (! isset($this->knownDatabaseColumns[$key])) {
            if (! $this->tableExists($table)) {
                return false;
            }

            $this->knownDatabaseColumns[$key] = Cache::remember(
                $this->schemaCacheKey('columns:'.$key),
                now()->addMinutes(5),
                fn (): array => array_fill_keys(
                    array_map('strtolower', Schema::getColumnListing($table)),
                    true,
                ),
            );
        }

        foreach ($columns as $column) {
            if (! isset($this->knownDatabaseColumns[$key][strtolower($column)])) {
                return false;
            }
        }

        return true;
    }

    private function schemaCacheKey(string $suffix): string
    {
        $connection = Schema::getConnection();

        return 'database-schema:'.hash('sha256', implode(':', [
            $connection->getName(),
            $connection->getDatabaseName(),
            $connection->getTablePrefix(),
            $suffix,
        ]));
    }
}
