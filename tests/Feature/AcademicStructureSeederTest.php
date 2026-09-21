<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AcademicStructureSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AcademicStructureSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_one_active_ceac_college_with_its_departments_and_eight_active_programs(): void
    {
        $this->seed(AcademicStructureSeeder::class);

        $college = DB::table('colleges')->where('code', 'CEAC')->sole();
        $departments = DB::table('departments')
            ->where('college_id', $college->id)
            ->where('is_active', true)
            ->get();
        $programs = DB::table('programs')
            ->whereIn('department_id', $departments->pluck('id'))
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $this->assertSame(config('academic.college.name'), $college->name);
        $this->assertTrue((bool) $college->is_active);
        $this->assertCount(count(config('academic.departments')), $departments);
        $this->assertEqualsCanonicalizing(
            collect(config('academic.departments'))->pluck('code')->all(),
            $departments->pluck('code')->all(),
        );
        $this->assertCount(8, $programs);
        $this->assertEqualsCanonicalizing(
            collect(config('academic.programs'))->pluck('code')->all(),
            $programs->pluck('code')->all(),
        );
    }

    public function test_it_deactivates_reference_records_outside_the_supported_structure(): void
    {
        DB::table('colleges')->insert([
            'id' => 20,
            'code' => 'OTHER',
            'name' => 'Other College',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('departments')->insert([
            'id' => 20,
            'college_id' => 20,
            'code' => 'OTHER-DEPT',
            'name' => 'Other Department',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('programs')->insert([
            'id' => 20,
            'department_id' => 20,
            'code' => 'OTHER-PROGRAM',
            'name' => 'Other Program',
            'degree_level' => 'Bachelor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(AcademicStructureSeeder::class);

        $this->assertSame(1, DB::table('colleges')->where('is_active', true)->count());
        $this->assertSame(count(config('academic.departments')), DB::table('departments')->where('is_active', true)->count());
        $this->assertSame(8, DB::table('programs')->where('is_active', true)->count());
        $this->assertFalse((bool) DB::table('colleges')->where('code', 'OTHER')->value('is_active'));
        $this->assertFalse((bool) DB::table('programs')->where('code', 'OTHER-PROGRAM')->value('is_active'));
    }

    public function test_it_normalizes_known_existing_user_affiliations_without_recreating_accounts(): void
    {
        $user = User::factory()->create([
            'department' => 'College of Information Technology',
            'program' => 'Bachelor of Science in Civil Engineering (BS CE)',
        ]);
        $originalPassword = $user->password;

        $this->seed(AcademicStructureSeeder::class);

        $user->refresh();

        $this->assertSame(config('academic.college.name'), $user->department);
        $this->assertSame(
            collect(config('academic.programs'))->firstWhere('code', 'BSCE')['label'],
            $user->program,
        );
        $this->assertSame($originalPassword, $user->password);
    }
}
