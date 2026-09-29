<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiftyOneAdminUserSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_filter_remains_scoped_when_searching_users(): void
    {
        $admin = User::factory()->create(['name' => 'هدف مدیر'])->forceFill(['role' => 'admin']);
        $admin->save();
        $user = User::factory()->create(['name' => 'هدف کاربر']);

        $response = $this->actingAs($admin)
            ->get(route('admin.users.index', ['q' => 'هدف', 'role' => 'user']))
            ->assertOk()
            ->assertSee($user->name);

        $this->assertStringNotContainsString('text-slate-800">'.$admin->name, $response->getContent());
    }
}
