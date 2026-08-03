<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->setRowLevelSecurity(true);

        DB::unprepared(<<<'SQL'
            REVOKE ALL PRIVILEGES ON ALL TABLES IN SCHEMA public FROM anon, authenticated;
            REVOKE ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public FROM anon, authenticated;
            REVOKE EXECUTE ON ALL FUNCTIONS IN SCHEMA public FROM anon, authenticated;

            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                REVOKE ALL PRIVILEGES ON TABLES FROM anon, authenticated;
            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                REVOKE ALL PRIVILEGES ON SEQUENCES FROM anon, authenticated;
            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                REVOKE EXECUTE ON FUNCTIONS FROM anon, authenticated;
            SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $this->setRowLevelSecurity(false);

        DB::unprepared(<<<'SQL'
            GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO anon, authenticated;
            GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO anon, authenticated;
            GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO anon, authenticated;

            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO anon, authenticated;
            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                GRANT USAGE, SELECT ON SEQUENCES TO anon, authenticated;
            ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public
                GRANT EXECUTE ON FUNCTIONS TO anon, authenticated;
            SQL);
    }

    private function setRowLevelSecurity(bool $enabled): void
    {
        $setting = $enabled ? 'ENABLE' : 'DISABLE';
        $tables = DB::select(<<<'SQL'
            SELECT tablename
            FROM pg_catalog.pg_tables
            WHERE schemaname = 'public'
              AND tableowner = current_user
            ORDER BY tablename
            SQL);

        foreach ($tables as $table) {
            $identifier = '"'.str_replace('"', '""', $table->tablename).'"';

            DB::statement("ALTER TABLE public.{$identifier} {$setting} ROW LEVEL SECURITY");
        }
    }
};
