<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->string('actor_context', 64)->nullable()->after('actor_email');
            $table->string('outcome', 16)->default('succeeded')->after('event');
            $table->index(['actor_context', 'created_at'], 'audit_logs_actor_context_created_index');
            $table->index(['outcome', 'created_at'], 'audit_logs_outcome_created_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION prevent_audit_log_mutation()
                RETURNS trigger AS $$
                BEGIN
                    IF TG_OP = 'DELETE' THEN
                        RAISE EXCEPTION 'audit logs are append-only';
                    END IF;

                    IF OLD.user_id IS NOT NULL
                        AND NEW.user_id IS NULL
                        AND (to_jsonb(OLD) - 'user_id') = (to_jsonb(NEW) - 'user_id') THEN
                        RETURN NEW;
                    END IF;

                    RAISE EXCEPTION 'audit logs are append-only';
                END;
                $$ LANGUAGE plpgsql;

                DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs;
                CREATE TRIGGER audit_logs_append_only
                BEFORE UPDATE OR DELETE ON audit_logs
                FOR EACH ROW EXECUTE FUNCTION prevent_audit_log_mutation();
                SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS audit_logs_append_only ON audit_logs');
            DB::unprepared('DROP FUNCTION IF EXISTS prevent_audit_log_mutation()');
        }

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex('audit_logs_actor_context_created_index');
            $table->dropIndex('audit_logs_outcome_created_index');
            $table->dropColumn(['actor_context', 'outcome']);
        });
    }
};
