<?php

namespace Tests\Feature\OfficialForms;

use App\Enums\UserType;
use App\Models\OfficialFormDefinition;
use App\Models\User;
use App\Modules\OfficialForms\Actions\SyncOfficialFormCatalog;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FormPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_form_permissions_catalog_is_seeded_idempotently(): void
    {
        $this->assertDatabaseHas('permissions', [
            'name' => 'forms.res-026.view',
            'module' => 'Official Research Forms',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'forms.res-045.certify',
            'module' => 'Official Research Forms',
        ]);

        // Re-run seeder to verify idempotency
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'forms.res-026.view',
            'module' => 'Official Research Forms',
        ]);
    }

    public function test_specialist_roles_have_default_form_permissions(): void
    {
        $instructorRole = Role::findByName('research-instructor', 'web');
        $this->assertTrue($instructorRole->hasPermissionTo('forms.res-040.view'));
        $this->assertTrue($instructorRole->hasPermissionTo('forms.res-041.endorse'));
        $this->assertFalse($instructorRole->hasPermissionTo('dashboards.facilitator.view'));

        $editorRole = Role::findByName('language-editor', 'web');
        $this->assertTrue($editorRole->hasPermissionTo('forms.res-045.certify'));

        $techEditorRole = Role::findByName('technical-editor', 'web');
        $this->assertTrue($techEditorRole->hasPermissionTo('forms.res-046.certify'));

        $validatorRole = Role::findByName('instrument-validator', 'web');
        $this->assertTrue($validatorRole->hasPermissionTo('forms.res-043a.validate'));
    }

    public function test_admin_created_custom_roles_survive_reseeding(): void
    {
        $customRole = Role::create(['name' => 'external-consultant', 'guard_name' => 'web']);
        $customRole->givePermissionTo(['forms.res-032.view', 'forms.res-032.sign']);

        // Reseed permission catalog
        $this->seed(RolePermissionSeeder::class);

        $this->assertTrue($customRole->fresh()->hasPermissionTo('forms.res-032.view'));
        $this->assertTrue($customRole->fresh()->hasPermissionTo('forms.res-032.sign'));
    }

    public function test_multi_role_user_receives_union_of_form_permissions(): void
    {
        $faculty = User::factory()->create(['user_type' => UserType::Faculty]);
        $faculty->assignRole(['thesis-adviser', 'language-editor']);

        $permissions = $faculty->getAllPermissions()->pluck('name');

        $this->assertTrue($permissions->contains('forms.res-031.view'));
        $this->assertTrue($permissions->contains('forms.res-045.certify'));
    }

    public function test_official_form_catalog_synchronizes_definitions(): void
    {
        (new SyncOfficialFormCatalog)->handle();

        $this->assertDatabaseHas('official_form_definitions', [
            'code' => 'RES-026',
            'ownership_scope' => 'research_group',
            'cardinality' => 'single_per_group',
        ]);

        $this->assertDatabaseHas('official_form_definitions', [
            'code' => 'RES-041',
            'ownership_scope' => 'research_class',
            'cardinality' => 'repeatable',
        ]);

        $this->assertSame(25, OfficialFormDefinition::query()->count());
    }
}
