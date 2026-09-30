<?php

namespace Tests\Feature;

use App\Models\MobileOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PhaseOneHundredThirtyOneMobileOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_mobile_is_verified_then_registered_without_a_password(): void
    {
        $this->post(route('register'), ['mobile' => '09124445566'])->assertRedirect(route('register'));
        $otp = MobileOtp::latest()->firstOrFail();
        $this->assertSame('register', $otp->purpose);

        $otp->update(['code_hash' => Hash::make('123456')]);
        $this->post(route('register.otp.verify'), ['mobile' => '09124445566', 'otp' => '123456'])->assertRedirect(route('register'));
        $this->get(route('register'))->assertOk()->assertSee('کد ملی (اختیاری)');
        $this->post(route('register'), ['name' => 'کاربر OTP', 'national_id' => '0012345678'])->assertRedirect(route('home'));

        $user = User::where('mobile', '09124445566')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('0012345678', $user->national_id);
    }

    public function test_existing_mobile_can_choose_password_or_otp_login(): void
    {
        $user = User::factory()->create(['mobile' => '09123334444']);

        $this->post(route('login'), ['mobile' => '09123334444'])->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('ارسال رمز یکبار مصرف با پیامک');
        $this->post(route('login.otp.request'), ['mobile' => '09123334444'])->assertRedirect(route('login'));
        $otp = MobileOtp::where('purpose', 'login')->latest()->firstOrFail();
        $otp->update(['code_hash' => Hash::make('654321')]);

        $this->post(route('login.otp.verify'), ['mobile' => '09123334444', 'otp' => '654321'])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }
}
