<?php

namespace Tests\Feature\Consultations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_booking_stub_superseded_by_phase_16_workflow_test(): void
    {
        $this->markTestSkipped('Legacy consultation booking test stub superseded by Phase 16 ConsultationWorkflowTest.');
    }
}
