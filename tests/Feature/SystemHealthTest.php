<?php

namespace Tests\Feature;

use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    public function test_health_endpoint_reports_database_readiness(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('responseCode', '00')
            ->assertJsonPath('data.status', 'connected');
    }
}
