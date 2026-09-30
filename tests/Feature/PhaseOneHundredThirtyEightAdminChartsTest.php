<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredThirtyEightAdminChartsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_exposes_trend_and_status_chart_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-chart="listing-trend"', false)
            ->assertSee('data-admin-chart="listing-status"', false)
            ->assertSee('listing-status-data', false);
    }
}
