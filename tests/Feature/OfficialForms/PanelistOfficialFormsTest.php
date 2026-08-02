<?php

namespace Tests\Feature\OfficialForms;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelistOfficialFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_panelist_dashboard_lists_every_required_official_form(): void
    {
        $panelist = User::factory()->create();
        $panelist->assignRole('panelist');

        $response = $this->actingAs($panelist)
            ->get(route('panelist.dashboard', ['tab' => 'forms', 'form' => 'RES-036']))
            ->assertOk()
            ->assertSee("activeOfficialForm = 'RES-036'", false);

        foreach (array_keys(config('official-forms.panelist')) as $code) {
            $response->assertSee($code);
        }

        $response
            ->assertDontSee('Phase 1: Title Approval')
            ->assertDontSee('Phase 3: Consultation and Endorsement')
            ->assertDontSee('Phase 7: Editing, Reproduction, and Completion')
            ->assertSee('Phase 2: Adviser and Panelist Assignment')
            ->assertSee('Phase 4: Proposal / Final Defense')
            ->assertSee('Phase 5: Revisions')
            ->assertSee('Phase 6: Instrument Validation and Data Gathering');
    }

    public function test_only_panelist_specific_forms_have_panelist_blade_files(): void
    {
        foreach (['RES-028', 'RES-036', 'RES-037'] as $code) {
            $this->assertFileExists(
                resource_path('views/pages/panelist/forms/'.strtolower($code).'.blade.php'),
            );
            $this->assertArrayNotHasKey('shared_with', config("official-forms.panelist.{$code}"));
        }

        foreach (config('official-forms.panelist') as $code => $definition) {
            $sharedWith = $definition['shared_with'] ?? null;

            if ($sharedWith !== null) {
                $this->assertFileDoesNotExist(
                    resource_path('views/pages/panelist/forms/'.strtolower($code).'.blade.php'),
                );
                $this->assertFileExists(
                    resource_path("views/pages/{$sharedWith}/forms/".strtolower($code).'.blade.php'),
                );
            }
        }
    }
}
