<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoHundredFiftySevenAdminUserRegistrationDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_user_list_displays_registration_date_in_persian_digits(): void
    {
        $admin = User::factory()->create([
            'name' => 'مدیر تست',
            'role' => 'admin',
        ]);
        $user = User::factory()->create([
            'name' => 'کاربر تاریخ ثبت‌نام',
            'created_at' => '2024-03-20 10:30:00',
            'updated_at' => '2024-03-20 10:30:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('تاریخ ثبت‌نام')
            ->assertSee('۱۴۰۳/۰۱/۰۱')
            ->assertSee('کاربر تاریخ ثبت‌نام');
    }
}
