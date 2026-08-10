<?php

namespace Tests\Feature\Consultations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdviserConsultationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_adviser_consultation_management_stub_superseded_by_phase_16_workflow_test(): void
    {
        $this->markTestSkipped('Legacy adviser consultation management test stub superseded by Phase 16 ConsultationWorkflowTest.');
    }
}
