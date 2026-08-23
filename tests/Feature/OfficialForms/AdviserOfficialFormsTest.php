<?php

namespace Tests\Feature\OfficialForms;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdviserOfficialFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_adviser_dashboard_lists_every_required_official_form(): void
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        $response = $this->actingAs($adviser)
            ->get(route('adviser.dashboard', ['tab' => 'forms', 'form' => 'RES-027']))
            ->assertOk()
            ->assertSee("activeOfficialForm = 'RES-027'", false);

        foreach (array_keys(config('official-forms.adviser')) as $code) {
            $response->assertSee($code);
        }

        $this->assertSame([
            'RES-026', 'RES-027', 'RES-038', 'RES-031', 'RES-032', 'RES-033', 'RES-034',
            'RES-035', 'RES-040', 'RES-042', 'RES-044', 'RES-048', 'RES-049',
        ], array_keys(config('official-forms.adviser')));
        $response
            ->assertSee('Official Research Forms')
            ->assertSee('Invitation to Research Adviser')
            ->assertSee('Certificate of Authentic Authorship')
            ->assertSee('Open Workspace to Edit & Save', false);
    }

    public function test_only_adviser_specific_forms_have_adviser_blade_files(): void
    {
        $adviserOnlyForms = ['RES-027', 'RES-033', 'RES-035', 'RES-038', 'RES-040', 'RES-044'];

        foreach ($adviserOnlyForms as $code) {
            $this->assertFileExists(
                resource_path('views/pages/adviser/forms/'.strtolower($code).'.blade.php'),
            );
            $this->assertArrayNotHasKey('shared_with', config("official-forms.adviser.{$code}"));
        }

        foreach (config('official-forms.adviser') as $code => $definition) {
            if (($definition['shared_with'] ?? null) === 'student') {
                $this->assertFileDoesNotExist(
                    resource_path('views/pages/adviser/forms/'.strtolower($code).'.blade.php'),
                );
                $this->assertFileExists(
                    resource_path('views/pages/student/forms/'.strtolower($code).'.blade.php'),
                );
            }
        }
    }
}
