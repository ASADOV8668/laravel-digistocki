<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseSixtyOneActiveUserGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_user_cannot_access_private_user_pages(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_inactive_user_can_still_log_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect('/');

        $this->assertGuest();
    }
}
