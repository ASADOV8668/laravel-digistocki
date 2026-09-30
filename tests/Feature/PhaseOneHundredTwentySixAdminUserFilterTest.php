<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredTwentySixAdminUserFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_role_filter_is_ignored_safely(): void
    {
        $admin = User::factory()->create(['name' => 'مدیر فیلتر کاربران'])->forceFill(['role' => 'admin']);
        $admin->save();
        $user = User::factory()->create(['name' => 'کاربر فیلتر کاربران']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['role' => 'not-a-role']))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee($user->name);
    }
}
