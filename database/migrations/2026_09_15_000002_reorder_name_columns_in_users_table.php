<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            DO \$\$
            DECLARE
                r RECORD;
            BEGIN
                CREATE TEMP TABLE tmp_fks (
                    tbl text,
                    conname text,
                    def text
                ) ON COMMIT DROP;

                INSERT INTO tmp_fks
                SELECT conrelid::regclass::text, conname, pg_get_constraintdef(oid)
                FROM pg_constraint
                WHERE confrelid = 'users'::regclass;

                FOR r IN SELECT tbl, conname FROM tmp_fks LOOP
                    EXECUTE format('ALTER TABLE %s DROP CONSTRAINT %I', r.tbl, r.conname);
                END LOOP;

                ALTER INDEX users_pkey RENAME TO users_old_pkey;
                ALTER INDEX users_email_unique RENAME TO users_old_email_unique;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_student_id_unique') THEN
                    ALTER INDEX users_student_id_unique RENAME TO users_old_student_id_unique;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_status_index') THEN
                    ALTER INDEX users_status_index RENAME TO users_old_status_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_approved_at_index') THEN
                    ALTER INDEX users_approved_at_index RENAME TO users_old_approved_at_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_first_name_index') THEN
                    ALTER INDEX users_first_name_index RENAME TO users_old_first_name_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_last_name_index') THEN
                    ALTER INDEX users_last_name_index RENAME TO users_old_last_name_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_user_type_index') THEN
                    ALTER INDEX users_user_type_index RENAME TO users_old_user_type_index;
                END IF;

                ALTER TABLE users RENAME TO users_old;

                CREATE TABLE users (
                    id BIGINT NOT NULL DEFAULT nextval('users_id_seq'::regclass),
                    first_name VARCHAR(100),
                    middle_name VARCHAR(100),
                    last_name VARCHAR(100),
                    suffix VARCHAR(20),
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    password VARCHAR(255) NOT NULL,
                    status VARCHAR(255) NOT NULL DEFAULT 'pending',
                    approved_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    remember_token VARCHAR(100),
                    created_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    student_id VARCHAR(255),
                    program VARCHAR(255),
                    year_level VARCHAR(255),
                    department VARCHAR(255),
                    user_type VARCHAR(20) NOT NULL DEFAULT 'faculty',
                    profile_photo_disk VARCHAR(32),
                    profile_photo_path VARCHAR(512),
                    profile_photo_mime_type VARCHAR(100),
                    profile_photo_size BIGINT,
                    profile_photo_updated_at TIMESTAMP WITH TIME ZONE,
                    CONSTRAINT users_pkey PRIMARY KEY (id),
                    CONSTRAINT users_email_unique UNIQUE (email),
                    CONSTRAINT users_student_id_unique UNIQUE (student_id)
                );

                CREATE INDEX users_status_index ON users (status);
                CREATE INDEX users_approved_at_index ON users (approved_at);
                CREATE INDEX users_first_name_index ON users (first_name);
                CREATE INDEX users_last_name_index ON users (last_name);
                CREATE INDEX users_user_type_index ON users (user_type);

                INSERT INTO users (
                    id, first_name, middle_name, last_name, suffix, name, email, email_verified_at,
                    password, status, approved_at, remember_token, created_at, updated_at,
                    student_id, program, year_level, department, user_type,
                    profile_photo_disk, profile_photo_path, profile_photo_mime_type, profile_photo_size, profile_photo_updated_at
                )
                SELECT
                    id, first_name, middle_name, last_name, suffix, name, email, email_verified_at,
                    password, status, approved_at, remember_token, created_at, updated_at,
                    student_id, program, year_level, department, user_type,
                    profile_photo_disk, profile_photo_path, profile_photo_mime_type, profile_photo_size, profile_photo_updated_at
                FROM users_old;

                ALTER SEQUENCE users_id_seq OWNED BY users.id;

                FOR r IN SELECT tbl, conname, def FROM tmp_fks LOOP
                    EXECUTE format('ALTER TABLE %s ADD CONSTRAINT %I %s', r.tbl, r.conname, r.def);
                END LOOP;

                DROP TABLE users_old CASCADE;
            END \$\$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            DO \$\$
            DECLARE
                r RECORD;
            BEGIN
                CREATE TEMP TABLE tmp_fks (
                    tbl text,
                    conname text,
                    def text
                ) ON COMMIT DROP;

                INSERT INTO tmp_fks
                SELECT conrelid::regclass::text, conname, pg_get_constraintdef(oid)
                FROM pg_constraint
                WHERE confrelid = 'users'::regclass;

                FOR r IN SELECT tbl, conname FROM tmp_fks LOOP
                    EXECUTE format('ALTER TABLE %s DROP CONSTRAINT %I', r.tbl, r.conname);
                END LOOP;

                ALTER INDEX users_pkey RENAME TO users_old_pkey;
                ALTER INDEX users_email_unique RENAME TO users_old_email_unique;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_student_id_unique') THEN
                    ALTER INDEX users_student_id_unique RENAME TO users_old_student_id_unique;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_status_index') THEN
                    ALTER INDEX users_status_index RENAME TO users_old_status_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_approved_at_index') THEN
                    ALTER INDEX users_approved_at_index RENAME TO users_old_approved_at_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_first_name_index') THEN
                    ALTER INDEX users_first_name_index RENAME TO users_old_first_name_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_last_name_index') THEN
                    ALTER INDEX users_last_name_index RENAME TO users_old_last_name_index;
                END IF;
                IF EXISTS (SELECT 1 FROM pg_indexes WHERE indexname = 'users_user_type_index') THEN
                    ALTER INDEX users_user_type_index RENAME TO users_old_user_type_index;
                END IF;

                ALTER TABLE users RENAME TO users_old;

                CREATE TABLE users (
                    id BIGINT NOT NULL DEFAULT nextval('users_id_seq'::regclass),
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    password VARCHAR(255) NOT NULL,
                    status VARCHAR(255) NOT NULL DEFAULT 'pending',
                    approved_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    remember_token VARCHAR(100),
                    created_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    updated_at TIMESTAMP(0) WITHOUT TIME ZONE,
                    student_id VARCHAR(255),
                    program VARCHAR(255),
                    year_level VARCHAR(255),
                    department VARCHAR(255),
                    user_type VARCHAR(20) NOT NULL DEFAULT 'faculty',
                    profile_photo_disk VARCHAR(32),
                    profile_photo_path VARCHAR(512),
                    profile_photo_mime_type VARCHAR(100),
                    profile_photo_size BIGINT,
                    profile_photo_updated_at TIMESTAMP WITH TIME ZONE,
                    first_name VARCHAR(100),
                    middle_name VARCHAR(100),
                    last_name VARCHAR(100),
                    suffix VARCHAR(20),
                    CONSTRAINT users_pkey PRIMARY KEY (id),
                    CONSTRAINT users_email_unique UNIQUE (email),
                    CONSTRAINT users_student_id_unique UNIQUE (student_id)
                );

                CREATE INDEX users_status_index ON users (status);
                CREATE INDEX users_approved_at_index ON users (approved_at);
                CREATE INDEX users_first_name_index ON users (first_name);
                CREATE INDEX users_last_name_index ON users (last_name);
                CREATE INDEX users_user_type_index ON users (user_type);

                INSERT INTO users (
                    id, name, email, email_verified_at, password, status, approved_at,
                    remember_token, created_at, updated_at, student_id, program,
                    year_level, department, user_type, profile_photo_disk,
                    profile_photo_path, profile_photo_mime_type, profile_photo_size,
                    profile_photo_updated_at, first_name, middle_name, last_name, suffix
                )
                SELECT
                    id, name, email, email_verified_at, password, status, approved_at,
                    remember_token, created_at, updated_at, student_id, program,
                    year_level, department, user_type, profile_photo_disk,
                    profile_photo_path, profile_photo_mime_type, profile_photo_size,
                    profile_photo_updated_at, first_name, middle_name, last_name, suffix
                FROM users_old;

                ALTER SEQUENCE users_id_seq OWNED BY users.id;

                FOR r IN SELECT tbl, conname, def FROM tmp_fks LOOP
                    EXECUTE format('ALTER TABLE %s ADD CONSTRAINT %I %s', r.tbl, r.conname, r.def);
                END LOOP;

                DROP TABLE users_old CASCADE;
            END \$\$;
        ");
    }
};
