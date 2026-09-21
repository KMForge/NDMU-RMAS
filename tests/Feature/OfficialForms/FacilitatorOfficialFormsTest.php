<?php

namespace Tests\Feature\OfficialForms;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacilitatorOfficialFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_facilitator_dashboard_lists_every_required_official_form(): void
    {
        $facilitator = User::factory()->create();
        $facilitator->assignRole('research-facilitator');
        $facilitator->givePermissionTo(
            collect(config('official-forms.facilitator'))
                ->keys()
                ->map(fn (string $code): string => 'forms.'.strtolower($code).'.view')
                ->all(),
        );

        $response = $this->actingAs($facilitator)
            ->get(route('facilitator.dashboard', ['tab' => 'forms', 'form' => 'RES-043A']))
            ->assertOk();

        foreach (array_keys(config('official-forms.facilitator')) as $code) {
            $response->assertSee($code);
        }
    }

    public function test_only_facilitator_specific_forms_have_facilitator_blade_files(): void
    {
        foreach (['RES-041', 'RES-043A', 'RES-043B', 'RES-045', 'RES-046', 'RES-047'] as $code) {
            $this->assertFileExists(
                resource_path('views/pages/facilitator/forms/'.strtolower($code).'.blade.php'),
            );
            $this->assertArrayNotHasKey('shared_with', config("official-forms.facilitator.{$code}"));
        }

        foreach (config('official-forms.facilitator') as $code => $definition) {
            $sharedWith = $definition['shared_with'] ?? null;

            if ($sharedWith !== null) {
                $this->assertFileDoesNotExist(
                    resource_path('views/pages/facilitator/forms/'.strtolower($code).'.blade.php'),
                );
                $this->assertFileExists(
                    resource_path("views/pages/{$sharedWith}/forms/".strtolower($code).'.blade.php'),
                );
            }
        }
    }
}
