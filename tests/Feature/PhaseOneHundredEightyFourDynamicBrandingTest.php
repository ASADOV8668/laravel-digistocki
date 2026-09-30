<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredEightyFourDynamicBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_admin_surfaces_use_the_configured_site_title(): void
    {
        SystemOption::create(['key' => 'site_title', 'value' => 'بازارک موبایل', 'type' => 'string']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('بازارک موبایل', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('بازارک موبایل', false);

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('بازارک موبایل', false);
    }
}
