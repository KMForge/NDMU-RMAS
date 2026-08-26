<?php

namespace Tests\Feature\ReportsAnalytics;

use App\Enums\UserType;
use App\Models\ResearchClass;
use App\Models\User;
use App\Modules\ReportsAnalytics\Exports\CsvReportExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ReportsAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['dashboards.admin.view', 'dashboards.facilitator.view', 'reports.view', 'reports.export'] as $permission) {
            Permission::findOrCreate($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_guest_cannot_view_or_export_reports(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.csv', 'research-summary'))->assertRedirect(route('login'));
    }

    public function test_admin_access_is_permission_driven(): void
    {
        $admin = $this->user(UserType::Admin, ['dashboards.admin.view']);
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertForbidden();

        $admin->givePermissionTo('reports.view');
        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee('Reports &amp; Analytics', false);
    }

    public function test_view_permission_does_not_grant_export_permission(): void
    {
        $admin = $this->user(UserType::Admin, ['dashboards.admin.view', 'reports.view']);
        $this->actingAs($admin)->get(route('admin.reports.csv', 'research-summary'))->assertForbidden();
    }

    public function test_authorized_csv_export_has_secure_headers_and_audit_event(): void
    {
        $admin = $this->user(UserType::Admin, ['dashboards.admin.view', 'reports.view', 'reports.export']);
        $response = $this->actingAs($admin)->get(route('admin.reports.csv', 'research-summary'));

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $response->headers->get('content-disposition'));
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'event' => 'report.exported', 'outcome' => 'succeeded']);
    }

    public function test_authorized_html_and_pdf_use_the_same_empty_scoped_dataset(): void
    {
        $admin = $this->user(UserType::Admin, ['dashboards.admin.view', 'reports.view', 'reports.export']);

        $this->actingAs($admin)
            ->get(route('admin.reports.show', 'milestone-completion'))
            ->assertOk()
            ->assertSee('No authorized records match the selected filters.');

        $pdf = $this->actingAs($admin)->get(route('admin.reports.pdf', 'milestone-completion'));
        $pdf->assertOk();
        $this->assertSame('application/pdf', $pdf->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', (string) $pdf->headers->get('content-disposition'));
    }

    public function test_facilitator_cannot_filter_by_another_facilitators_class(): void
    {
        $facilitator = $this->user(UserType::Faculty, ['dashboards.facilitator.view', 'reports.view']);
        $other = $this->user(UserType::Faculty);
        $foreignClass = new ResearchClass(['facilitator_id' => $other->id, 'creation_token' => (string) Str::uuid(), 'name' => 'Foreign class']);
        $foreignClass->setJoinCode('FOREIGN1');
        $foreignClass->save();

        $this->actingAs($facilitator)
            ->get(route('facilitator.reports.show', ['report' => 'research-stage-status', 'research_class_id' => $foreignClass->id]))
            ->assertSessionHasErrors('research_class_id');
    }

    public function test_csv_formula_prefixes_are_neutralized(): void
    {
        $exporter = app(CsvReportExporter::class);
        foreach (['=SUM(A1:A2)', '+1', '-1', '@cmd', "\tformula", "\rformula", "\nformula", '  =formula'] as $dangerous) {
            $this->assertStringStartsWith("'", $exporter->safeCell($dangerous));
        }
        $this->assertSame('ordinary text', $exporter->safeCell('ordinary text'));
        $this->assertSame('a,b"c', $exporter->safeCell('a,b"c'));
    }

    /** @param list<string> $permissions */
    private function user(UserType $type, array $permissions = []): User
    {
        $user = User::factory()->create(['user_type' => $type]);
        if ($permissions !== []) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }
}
