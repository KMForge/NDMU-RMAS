<?php

namespace Tests\Feature\OfficialForms;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentOfficialFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_student_dashboard_lists_the_student_official_forms(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('student.dashboard', ['tab' => 'forms', 'form' => 'RES-026']))
            ->assertOk()
            ->assertSee('RES-026')
            ->assertSee("activeOfficialForm = 'RES-026'", false)
            ->assertSee('Research Title Approval')
            ->assertSee('RES-049')
            ->assertSee('Certificate of Authentic Authorship');
    }

    public function test_authenticated_student_can_view_the_official_forms_pdf(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('student.official-forms.source', ['form' => 'RES-026']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('x-content-type-options', 'nosniff');
    }

    public function test_guest_cannot_view_the_official_forms_pdf(): void
    {
        $this->get(route('student.official-forms.source', ['form' => 'RES-026']))
            ->assertRedirect(route('login'));
    }

    public function test_non_student_cannot_view_the_official_forms_pdf(): void
    {
        $adviser = User::factory()->create();
        $adviser->assignRole('research-adviser');

        $this->actingAs($adviser)
            ->get(route('student.official-forms.source', ['form' => 'RES-026']))
            ->assertForbidden();
    }

    public function test_unknown_form_is_not_exposed(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        $this->actingAs($student)
            ->get(route('student.official-forms.source', ['form' => 'RES-999']))
            ->assertNotFound();
    }

    public function test_each_catalog_entry_serves_its_own_pdf(): void
    {
        $student = User::factory()->create();
        $student->assignRole('student-researcher');

        foreach (config('official-forms.student') as $code => $form) {
            $this->assertFileExists(resource_path('forms/student/'.$form['file']));

            $this->actingAs($student)
                ->get(route('student.official-forms.source', ['form' => $code]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }
}
