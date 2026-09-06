<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AcademicStructureSeeder extends Seeder
{
    public function run(): void
    {
        $requiredTables = ['colleges', 'departments', 'programs'];

        foreach ($requiredTables as $table) {
            if (! Schema::hasTable($table)) {
                $this->command?->warn("Academic structure seeding skipped because the {$table} table does not exist.");

                return;
            }
        }

        DB::transaction(function (): void {
            $now = now();
            $college = config('academic.college');
            $departments = config('academic.departments', []);
            $programs = config('academic.programs', []);

            DB::table('colleges')
                ->where('code', '<>', $college['code'])
                ->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            $collegeId = $this->upsertReferenceRecord(
                'colleges',
                $college['code'],
                [
                    'name' => $college['name'],
                    'is_active' => true,
                ],
            );

            $departmentIdsByCode = [];
            $departmentCodes = collect($departments)->pluck('code')->all();

            DB::table('departments')
                ->whereNotIn('code', $departmentCodes)
                ->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            foreach ($departments as $dept) {
                $deptId = $this->upsertReferenceRecord(
                    'departments',
                    $dept['code'],
                    [
                        'college_id' => $collegeId,
                        'name' => $dept['name'],
                        'is_active' => true,
                    ],
                );
                $departmentIdsByCode[$dept['code']] = $deptId;
            }

            $programCodes = collect($programs)->pluck('code')->all();

            DB::table('programs')
                ->whereNotIn('code', $programCodes)
                ->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

            foreach ($programs as $program) {
                $deptCode = $program['department_code'] ?? 'CSD';
                $deptId = $departmentIdsByCode[$deptCode] ?? array_values($departmentIdsByCode)[0];

                $this->upsertReferenceRecord(
                    'programs',
                    $program['code'],
                    [
                        'department_id' => $deptId,
                        'name' => $program['name'],
                        'degree_level' => 'Bachelor',
                        'is_active' => true,
                    ],
                );
            }

            $this->normalizeExistingUserAffiliations($college['name'], $programs);
        });

        $this->command?->info('CEAC and its eight academic programs were seeded successfully.');
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsertReferenceRecord(string $table, string $code, array $values): int
    {
        $existingId = DB::table($table)
            ->where('code', $code)
            ->value('id');

        if ($existingId !== null) {
            DB::table($table)
                ->where('id', $existingId)
                ->update([
                    ...$values,
                    'updated_at' => now(),
                ]);

            return (int) $existingId;
        }

        $id = ((int) DB::table($table)->max('id')) + 1;

        DB::table($table)->insert([
            'id' => $id,
            'code' => $code,
            ...$values,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    /**
     * @param  array<int, array{code: string, name: string, label: string}>  $programs
     */
    private function normalizeExistingUserAffiliations(string $collegeName, array $programs): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'department')) {
            DB::table('users')
                ->whereNotNull('department')
                ->where('department', '<>', $collegeName)
                ->update([
                    'department' => $collegeName,
                    'updated_at' => now(),
                ]);
        }

        if (! Schema::hasColumn('users', 'program')) {
            return;
        }

        $legacyProgramValues = [
            'BSARCH' => ['BSARCH', 'BS Architecture', 'Bachelor of Science in Architecture (BS Arch)'],
            'BSCE' => ['BSCE', 'BS Civil Engineering', 'Bachelor of Science in Civil Engineering (BS CE)'],
            'BSCPE' => ['BSCPE', 'BS Computer Engineering'],
            'BSCS' => ['BSCS', 'BS Computer Science', 'Bachelor of Science in Computer Science (BSCS)'],
            'BSEE' => ['BSEE', 'BS Electrical Engineering'],
            'BSECE' => ['BSECE', 'BS Electronics Engineering', 'Bachelor of Science in Electronics Engineering (BS EcE)'],
            'BSIT' => ['BSIT', 'BS Information Technology', 'Bachelor of Science in Information Technology (BSIT)'],
            'BLIS' => ['BLIS'],
        ];

        foreach ($programs as $program) {
            DB::table('users')
                ->whereIn('program', $legacyProgramValues[$program['code']] ?? [])
                ->update([
                    'program' => $program['label'],
                    'updated_at' => now(),
                ]);
        }
    }
}
