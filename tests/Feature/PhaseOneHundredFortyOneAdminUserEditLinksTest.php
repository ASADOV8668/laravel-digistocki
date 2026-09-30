<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredFortyOneAdminUserEditLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_feed_links_user_name_to_editor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['name' => 'کاربر قابل ویرایش']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee(route('admin.users.edit', $user), false)->assertSee($user->name);
    }
}
