<?php

namespace Tests\API\V1;

use Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_status_endpoint_is_versioned_and_healthy(): void
    {
        $this->getJson('/api/v1/status')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }
}
