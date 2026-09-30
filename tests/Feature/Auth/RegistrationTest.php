<?php

namespace Tests\Feature\Auth;

use App\Models\MobileOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->post('/register', ['mobile' => '09121234567'])->assertRedirect(route('register'));
        MobileOtp::latest()->firstOrFail()->update(['code_hash' => Hash::make('123456')]);
        $this->post(route('register.otp.verify'), ['mobile' => '09121234567', 'otp' => '123456'])->assertRedirect(route('register'));
        $response = $this->post('/register', ['name' => 'Test User']);

        $this->assertAuthenticated();
        $response->assertRedirect(route('home'));
    }
}
