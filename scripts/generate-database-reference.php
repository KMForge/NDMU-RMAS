<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$connection = DB::connection();

if ($connection->getDriverName() !== 'pgsql') {
    fwrite(STDERR, "The database reference generator requires the pgsql connection.\n");
    exit(1);
}

/** @return array<int, object> */
function queryRows(string $sql, array $bindings = []): array
{
    return DB::select($sql, $bindings);
}

function markdown(mixed $value): string
{
    if ($value === null || $value === '') {
        return '—';
    }

    $text = str_replace(["\r", "\n"], ['', '<br>'], (string) $value);

    return str_replace('|', '\\|', $text);
}

function yesNo(bool $value): string
{
    return $value ? 'Yes' : 'No';
}

$database = (string) $connection->getDatabaseName();
$config = config('database.connections.pgsql');
$server = queryRows('select version() as version, current_user as current_user, current_database() as database_name')[0];
$tables = queryRows(<<<'SQL'
    select
        c.relname as table_name,
        c.relrowsecurity as rls_enabled,
        c.relforcerowsecurity as rls_forced,
        pg_get_userbyid(c.relowner) as owner,
        obj_description(c.oid, 'pg_class') as comment
    from pg_class c
    join pg_namespace n on n.oid = c.relnamespace
    where n.nspname = 'public' and c.relkind = 'r'
    order by c.relname
SQL);
$views = queryRows(<<<'SQL'
    select table_name, view_definition
    from information_schema.views
    where table_schema = 'public'
    order by table_name
SQL);
$columns = queryRows(<<<'SQL'
    select
        c.relname as table_name,
        a.attnum as ordinal_position,
        a.attname as column_name,
        pg_catalog.format_type(a.atttypid, a.atttypmod) as formatted_type,
        not a.attnotnull as is_nullable,
        pg_get_expr(ad.adbin, ad.adrelid) as column_default,
        a.attidentity as identity_kind,
        a.attgenerated as generated_kind,
        col_description(a.attrelid, a.attnum) as comment
    from pg_attribute a
    join pg_class c on c.oid = a.attrelid
    join pg_namespace n on n.oid = c.relnamespace
    left join pg_attrdef ad on ad.adrelid = a.attrelid and ad.adnum = a.attnum
    where n.nspname = 'public'
      and c.relkind = 'r'
      and a.attnum > 0
      and not a.attisdropped
    order by c.relname, a.attnum
SQL);
$constraints = queryRows(<<<'SQL'
    select
        c.relname as table_name,
        con.conname as constraint_name,
        case con.contype
            when 'p' then 'PRIMARY KEY'
            when 'f' then 'FOREIGN KEY'
            when 'u' then 'UNIQUE'
            when 'c' then 'CHECK'
            when 'x' then 'EXCLUDE'
            else con.contype::text
        end as constraint_type,
        pg_get_constraintdef(con.oid, true) as definition
    from pg_constraint con
    join pg_class c on c.oid = con.conrelid
    join pg_namespace n on n.oid = c.relnamespace
    where n.nspname = 'public'
    order by c.relname, constraint_type, con.conname
SQL);
$indexes = queryRows(<<<'SQL'
    select tablename as table_name, indexname as index_name, indexdef as definition
    from pg_indexes
    where schemaname = 'public'
    order by tablename, indexname
SQL);
$policies = queryRows(<<<'SQL'
    select
        tablename as table_name,
        policyname as policy_name,
        permissive,
        array_to_string(roles, ', ') as roles,
        cmd,
        qual,
        with_check
    from pg_policies
    where schemaname = 'public'
    order by tablename, policyname
SQL);
$triggers = queryRows(<<<'SQL'
    select
        event_object_table as table_name,
        trigger_name,
        action_timing,
        string_agg(event_manipulation, ', ' order by event_manipulation) as events,
        action_statement
    from information_schema.triggers
    where trigger_schema = 'public'
    group by event_object_table, trigger_name, action_timing, action_statement
    order by event_object_table, trigger_name
SQL);
$sequences = queryRows(<<<'SQL'
    select sequence_name, data_type, start_value, minimum_value, maximum_value, increment
    from information_schema.sequences
    where sequence_schema = 'public'
    order by sequence_name
SQL);
$functions = queryRows(<<<'SQL'
    select
        p.proname as function_name,
        pg_get_function_identity_arguments(p.oid) as arguments,
        pg_get_function_result(p.oid) as result_type,
        l.lanname as language,
        case p.provolatile when 'i' then 'immutable' when 's' then 'stable' else 'volatile' end as volatility
    from pg_proc p
    join pg_namespace n on n.oid = p.pronamespace
    join pg_language l on l.oid = p.prolang
    where n.nspname = 'public'
    order by p.proname, arguments
SQL);
$extensions = queryRows('select extname, extversion from pg_extension order by extname');
$privileges = queryRows(<<<'SQL'
    select grantee, privilege_type, count(distinct table_name)::int as table_count
    from information_schema.role_table_grants
    where table_schema = 'public'
    group by grantee, privilege_type
    order by grantee, privilege_type
SQL);
$migrationLedger = queryRows('select migration, batch from migrations order by id');

$tableNames = array_map(fn (object $table): string => $table->table_name, $tables);
$columnsByTable = collect($columns)->groupBy('table_name');
$constraintsByTable = collect($constraints)->groupBy('table_name');
$indexesByTable = collect($indexes)->groupBy('table_name');
$policiesByTable = collect($policies)->groupBy('table_name');
$triggersByTable = collect($triggers)->groupBy('table_name');

$rowCounts = [];
foreach ($tableNames as $tableName) {
    $quoted = '"'.str_replace('"', '""', $tableName).'"';
    $rowCounts[$tableName] = (int) queryRows("select count(*)::bigint as aggregate from public.{$quoted}")[0]->aggregate;
}

$modelMappings = [];
foreach (glob(app_path('Models/*.php')) ?: [] as $modelFile) {
    $class = 'App\\Models\\'.pathinfo($modelFile, PATHINFO_FILENAME);

    if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
        continue;
    }

    /** @var Model $model */
    $model = new $class;
    if (in_array($model->getTable(), $tableNames, true)) {
        $modelMappings[] = ['model' => $class, 'table' => $model->getTable()];
    }
}
usort($modelMappings, fn (array $left, array $right): int => $left['table'] <=> $right['table']);

$migrationFiles = collect(glob(database_path('migrations/*.php')) ?: [])
    ->map(fn (string $path): string => basename($path))
    ->sort()
    ->values();
$appliedMigrationNames = collect($migrationLedger)->pluck('migration');
$migrationTouches = [];
foreach (glob(database_path('migrations/*.php')) ?: [] as $migrationPath) {
    $migrationSource = file_get_contents($migrationPath);
    if ($migrationSource === false) {
        continue;
    }

    preg_match_all(
        "/Schema::(create|table|dropIfExists)\\(\\s*['\"]([^'\"]+)['\"]/",
        $migrationSource,
        $matches,
        PREG_SET_ORDER,
    );

    foreach ($matches as $match) {
        $tableName = $match[2];
        if (! in_array($tableName, $tableNames, true)) {
            continue;
        }

        $operation = match ($match[1]) {
            'create' => 'create',
            'table' => 'alter',
            'dropIfExists' => 'drop in down/cleanup path',
        };
        $migrationTouches[$tableName][] = basename($migrationPath).' ('.$operation.')';
    }
}

$lines = [];
$lines[] = '# NDMU-RMAS Database Reference';
$lines[] = '';
$lines[] = '> Generated from the live PostgreSQL system catalog, Laravel runtime configuration, the applied `migrations` ledger, migration files, and Eloquent model metadata. No table, column, relationship, constraint, policy, index, or count in this document was invented manually.';
$lines[] = '';
$lines[] = 'Generated at: `'.now(config('app.timezone'))->format('Y-m-d H:i:s T').'`';
$lines[] = '';
$lines[] = '## Verification scope';
$lines[] = '';
$lines[] = '- Live source: PostgreSQL database `'.markdown($database).'`, schema `public`.';
$lines[] = '- Connection driver: `'.markdown($connection->getDriverName()).'`.';
$lines[] = '- Server-reported database: `'.markdown($server->database_name).'`.';
$lines[] = '- Server-reported current role: `'.markdown($server->current_user).'`.';
$lines[] = '- Server version: `'.markdown($server->version).'`.';
$lines[] = '- Secrets are intentionally excluded. Passwords, hashes, tokens, file paths, and row contents are not documented.';
$lines[] = '- Row counts are a point-in-time snapshot and will change as the application is used.';
$lines[] = '';
$lines[] = '## Safe connection profile';
$lines[] = '';
$lines[] = '| Setting | Verified value |';
$lines[] = '| --- | --- |';
$lines[] = '| Laravel connection | `'.markdown(config('database.default')).'` |';
$lines[] = '| Driver | `'.markdown($config['driver'] ?? null).'` |';
$lines[] = '| Host | `'.markdown($config['host'] ?? null).'` |';
$lines[] = '| Port | `'.markdown($config['port'] ?? null).'` |';
$lines[] = '| Database | `'.markdown($config['database'] ?? null).'` |';
$lines[] = '| Username | `'.markdown($config['username'] ?? null).'` |';
$lines[] = '| Schema search path | `'.markdown($config['search_path'] ?? 'public').'` |';
$lines[] = '| SSL mode | `'.markdown($config['sslmode'] ?? null).'` |';
$lines[] = '';
$lines[] = '## Catalog summary';
$lines[] = '';
$lines[] = '| Item | Count |';
$lines[] = '| --- | ---: |';
$lines[] = '| Public tables | '.count($tables).' |';
$lines[] = '| Public table columns | '.count($columns).' |';
$lines[] = '| Public views | '.count($views).' |';
$lines[] = '| Foreign keys | '.collect($constraints)->where('constraint_type', 'FOREIGN KEY')->count().' |';
$lines[] = '| Primary keys | '.collect($constraints)->where('constraint_type', 'PRIMARY KEY')->count().' |';
$lines[] = '| Unique constraints | '.collect($constraints)->where('constraint_type', 'UNIQUE')->count().' |';
$lines[] = '| Check constraints | '.collect($constraints)->where('constraint_type', 'CHECK')->count().' |';
$lines[] = '| Indexes, including constraint indexes | '.count($indexes).' |';
$lines[] = '| Tables with RLS enabled | '.collect($tables)->where('rls_enabled', true)->count().' |';
$lines[] = '| RLS policies | '.count($policies).' |';
$lines[] = '| User-defined public triggers | '.count($triggers).' |';
$lines[] = '| Public functions | '.count($functions).' |';
$lines[] = '| Public sequences | '.count($sequences).' |';
$lines[] = '| Applied migrations | '.count($migrationLedger).' |';
$lines[] = '| Eloquent models mapped to live tables | '.count($modelMappings).' |';
$lines[] = '';
$lines[] = '## Table inventory and current row counts';
$lines[] = '';
$lines[] = '| Table | Rows | Columns | RLS | Owner |';
$lines[] = '| --- | ---: | ---: | --- | --- |';
foreach ($tables as $table) {
    $lines[] = '| `'.markdown($table->table_name).'` | '.$rowCounts[$table->table_name].' | '.$columnsByTable->get($table->table_name, collect())->count().' | '.yesNo((bool) $table->rls_enabled).' | `'.markdown($table->owner).'` |';
}
$lines[] = '';
$lines[] = '## Relationship map';
$lines[] = '';
$foreignKeys = collect($constraints)->where('constraint_type', 'FOREIGN KEY')->values();
if ($foreignKeys->isEmpty()) {
    $lines[] = 'No foreign keys were reported by PostgreSQL.';
} else {
    $lines[] = '| Source table | Constraint | PostgreSQL definition |';
    $lines[] = '| --- | --- | --- |';
    foreach ($foreignKeys as $foreignKey) {
        $lines[] = '| `'.markdown($foreignKey->table_name).'` | `'.markdown($foreignKey->constraint_name).'` | `'.markdown($foreignKey->definition).'` |';
    }
}
$lines[] = '';
$lines[] = '## Eloquent model-to-table map';
$lines[] = '';
$lines[] = 'This map is obtained by instantiating each concrete class in `app/Models` and reading Eloquent’s runtime table name.';
$lines[] = '';
$lines[] = '| Eloquent model | Live table |';
$lines[] = '| --- | --- |';
foreach ($modelMappings as $mapping) {
    $lines[] = '| `'.markdown($mapping['model']).'` | `'.markdown($mapping['table']).'` |';
}
$lines[] = '';
$lines[] = '## Complete table definitions';
$lines[] = '';
foreach ($tables as $table) {
    $tableName = $table->table_name;
    $lines[] = '### `'.markdown($tableName).'`';
    $lines[] = '';
    $lines[] = '- Current rows: **'.$rowCounts[$tableName].'**';
    $lines[] = '- Owner: `'.markdown($table->owner).'`';
    $lines[] = '- RLS enabled: **'.yesNo((bool) $table->rls_enabled).'**';
    $lines[] = '- RLS forced for owner: **'.yesNo((bool) $table->rls_forced).'**';
    $sourceReferences = array_values(array_unique($migrationTouches[$tableName] ?? []));
    $lines[] = '- Migration source references: '.($sourceReferences === []
        ? 'No direct `Schema::create`, `Schema::table`, or `Schema::dropIfExists` reference was detected in the repository migration files.'
        : implode(', ', array_map(fn (string $source): string => '`'.markdown($source).'`', $sourceReferences)));
    if ($table->comment !== null) {
        $lines[] = '- Database comment: '.markdown($table->comment);
    }
    $lines[] = '';
    $lines[] = '#### Columns';
    $lines[] = '';
    $lines[] = '| # | Column | PostgreSQL type | Nullable | Default / generation | Comment |';
    $lines[] = '| ---: | --- | --- | --- | --- | --- |';
    foreach ($columnsByTable->get($tableName, collect()) as $column) {
        $generation = $column->column_default;
        if ($column->identity_kind !== '') {
            $generation = trim(($generation ? $generation.'; ' : '').'identity='.$column->identity_kind);
        }
        if ($column->generated_kind !== '') {
            $generation = trim(($generation ? $generation.'; ' : '').'generated='.$column->generated_kind);
        }
        $lines[] = '| '.$column->ordinal_position.' | `'.markdown($column->column_name).'` | `'.markdown($column->formatted_type).'` | '.yesNo((bool) $column->is_nullable).' | `'.markdown($generation).'` | '.markdown($column->comment).' |';
    }
    $lines[] = '';
    $lines[] = '#### Constraints';
    $lines[] = '';
    $tableConstraints = $constraintsByTable->get($tableName, collect());
    if ($tableConstraints->isEmpty()) {
        $lines[] = 'No PostgreSQL constraints reported.';
    } else {
        $lines[] = '| Name | Type | Definition |';
        $lines[] = '| --- | --- | --- |';
        foreach ($tableConstraints as $constraint) {
            $lines[] = '| `'.markdown($constraint->constraint_name).'` | '.markdown($constraint->constraint_type).' | `'.markdown($constraint->definition).'` |';
        }
    }
    $lines[] = '';
    $lines[] = '#### Indexes';
    $lines[] = '';
    $tableIndexes = $indexesByTable->get($tableName, collect());
    if ($tableIndexes->isEmpty()) {
        $lines[] = 'No indexes reported.';
    } else {
        $lines[] = '| Name | Definition |';
        $lines[] = '| --- | --- |';
        foreach ($tableIndexes as $index) {
            $lines[] = '| `'.markdown($index->index_name).'` | `'.markdown($index->definition).'` |';
        }
    }
    $lines[] = '';
    $lines[] = '#### Row-level security policies';
    $lines[] = '';
    $tablePolicies = $policiesByTable->get($tableName, collect());
    if ($tablePolicies->isEmpty()) {
        $lines[] = (bool) $table->rls_enabled
            ? '**RLS is enabled, but PostgreSQL reports no explicit policy for this table.**'
            : 'RLS is not enabled and no policy is defined.';
    } else {
        $lines[] = '| Policy | Mode | Roles | Command | USING | WITH CHECK |';
        $lines[] = '| --- | --- | --- | --- | --- | --- |';
        foreach ($tablePolicies as $policy) {
            $lines[] = '| `'.markdown($policy->policy_name).'` | '.markdown($policy->permissive).' | `'.markdown($policy->roles).'` | `'.markdown($policy->cmd).'` | `'.markdown($policy->qual).'` | `'.markdown($policy->with_check).'` |';
        }
    }
    $lines[] = '';
    $lines[] = '#### Triggers';
    $lines[] = '';
    $tableTriggers = $triggersByTable->get($tableName, collect());
    if ($tableTriggers->isEmpty()) {
        $lines[] = 'No user-defined trigger reported.';
    } else {
        $lines[] = '| Trigger | Timing | Events | Action |';
        $lines[] = '| --- | --- | --- | --- |';
        foreach ($tableTriggers as $trigger) {
            $lines[] = '| `'.markdown($trigger->trigger_name).'` | '.markdown($trigger->action_timing).' | '.markdown($trigger->events).' | `'.markdown($trigger->action_statement).'` |';
        }
    }
    $lines[] = '';
}
$lines[] = '## Views';
$lines[] = '';
if ($views === []) {
    $lines[] = 'No views exist in the `public` schema.';
} else {
    foreach ($views as $view) {
        $lines[] = '### `'.markdown($view->table_name).'`';
        $lines[] = '';
        $lines[] = '```sql';
        $lines[] = (string) $view->view_definition;
        $lines[] = '```';
        $lines[] = '';
    }
}
$lines[] = '';
$lines[] = '## Public functions';
$lines[] = '';
if ($functions === []) {
    $lines[] = 'No functions exist in the `public` schema.';
} else {
    $lines[] = '| Function | Arguments | Result | Language | Volatility |';
    $lines[] = '| --- | --- | --- | --- | --- |';
    foreach ($functions as $function) {
        $lines[] = '| `'.markdown($function->function_name).'` | `'.markdown($function->arguments).'` | `'.markdown($function->result_type).'` | `'.markdown($function->language).'` | '.markdown($function->volatility).' |';
    }
}
$lines[] = '';
$lines[] = '## Sequences';
$lines[] = '';
$lines[] = '| Sequence | Type | Start | Minimum | Maximum | Increment |';
$lines[] = '| --- | --- | ---: | ---: | ---: | ---: |';
foreach ($sequences as $sequence) {
    $lines[] = '| `'.markdown($sequence->sequence_name).'` | `'.markdown($sequence->data_type).'` | '.$sequence->start_value.' | '.$sequence->minimum_value.' | '.$sequence->maximum_value.' | '.$sequence->increment.' |';
}
$lines[] = '';
$lines[] = '## Installed PostgreSQL extensions';
$lines[] = '';
$lines[] = '| Extension | Version |';
$lines[] = '| --- | --- |';
foreach ($extensions as $extension) {
    $lines[] = '| `'.markdown($extension->extname).'` | `'.markdown($extension->extversion).'` |';
}
$lines[] = '';
$lines[] = '## Table privilege summary';
$lines[] = '';
$lines[] = '| Grantee | Privilege | Number of public tables |';
$lines[] = '| --- | --- | ---: |';
foreach ($privileges as $privilege) {
    $lines[] = '| `'.markdown($privilege->grantee).'` | `'.markdown($privilege->privilege_type).'` | '.$privilege->table_count.' |';
}
$lines[] = '';
$lines[] = '## Migration verification';
$lines[] = '';
$lines[] = '| Migration | Batch | Source file present |';
$lines[] = '| --- | ---: | --- |';
foreach ($migrationLedger as $migration) {
    $expectedFile = $migration->migration.'.php';
    $lines[] = '| `'.markdown($migration->migration).'` | '.$migration->batch.' | '.($migrationFiles->contains($expectedFile) ? 'Yes' : '**No**').' |';
}

$unappliedFiles = $migrationFiles
    ->reject(fn (string $file): bool => $appliedMigrationNames->contains(Str::beforeLast($file, '.php')))
    ->values();
$lines[] = '';
$lines[] = '### Migration files not present in the live migration ledger';
$lines[] = '';
if ($unappliedFiles->isEmpty()) {
    $lines[] = 'None. Every migration file in `database/migrations` is present in the live ledger.';
} else {
    foreach ($unappliedFiles as $file) {
        $lines[] = '- `'.$file.'`';
    }
}
$lines[] = '';
$lines[] = '## Interpretation and maintenance rules';
$lines[] = '';
$lines[] = '- Foreign-key behavior, nullability, defaults, unique rules, and checks must be taken from the PostgreSQL definitions above—not inferred from UI labels.';
$lines[] = '- RLS enabled without an explicit policy is recorded exactly as PostgreSQL reports it. Application authorization still belongs to Laravel middleware, Spatie permissions, policies, and record-scoped queries.';
$lines[] = '- Laravel framework infrastructure tables such as cache, sessions, queues, notifications, migrations, password resets, and Spatie RBAC tables are included because they physically exist in the active schema.';
$lines[] = '- Regenerate this file after applying a migration: `php scripts/generate-database-reference.php`.';
$lines[] = '- Review the resulting Git diff before committing because row counts and the generation timestamp are expected to change.';
$lines[] = '';

$target = base_path('docs/DATABASE_REFERENCE.md');
$content = implode("\n", $lines);

if (file_put_contents($target, $content) === false) {
    fwrite(STDERR, "Unable to write {$target}.\n");
    exit(1);
}

fwrite(STDOUT, "Generated {$target} with ".count($tables).' tables and '.count($columns)." columns.\n");
