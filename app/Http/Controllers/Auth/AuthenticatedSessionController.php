<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\MobileOtpService;
use App\Services\SystemOptions;
use App\Support\MobileNumber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, SystemOptions $options): View
    {
        $step = $request->session()->has('login_otp_mobile') ? 'otp' : ($request->session()->has('login_identified_mobile') ? 'password' : 'mobile');
        $mobile = $request->session()->get('login_otp_mobile', $request->session()->get('login_identified_mobile'));

        return view('auth.login', [
            'registrationMode' => $options->registrationMode(),
            'step' => $step,
            'mobile' => $mobile,
            'otpExpiresAt' => $request->session()->get('login_otp_expires_at'),
        ]);
    }

    public function store(LoginRequest $request, MobileOtpService $otpService): RedirectResponse|View
    {
        $mobile = $request->validated()['mobile'];

        if (! $request->filled('password')) {
            $user = User::query()->where('mobile', $mobile)->first();
            if (! $user) {
                $otp = $otpService->issue($mobile, 'register');
                if (! $otp) {
                    return back()->withErrors(['mobile' => 'ارسال کد تأیید انجام نشد؛ لطفاً بعداً دوباره تلاش کنید.']);
                }

                $request->session()->put(['registration_mobile' => $mobile, 'registration_otp_expires_at' => $otp->expires_at->timestamp]);

                return redirect()->route('register')->with('status', 'کد تأیید ثبت‌نام به موبایل شما ارسال شد.');
            }

            $request->session()->put('login_identified_mobile', $mobile);

            return redirect()->route('login');
        }

        $request->authenticate();
        $request->session()->forget('login_identified_mobile');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function requestOtp(Request $request, MobileOtpService $otpService): RedirectResponse
    {
        $request->merge(['mobile' => MobileNumber::normalizeLocal($request->input('mobile'))]);
        $validated = $request->validate(
            ['mobile' => ['required', 'regex:/^09\d{9}$/']],
            ['mobile.regex' => 'شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود؛ استفاده از ۹۸ یا + مجاز نیست.']
        );
        $user = User::query()->where('mobile', $validated['mobile'])->where('is_active', true)->first();
        if (! $user) {
            return back()->withErrors(['mobile' => 'این شماره در سامانه پیدا نشد؛ ابتدا ثبت‌نام کنید.']);
        }

        $otp = $otpService->issue($validated['mobile'], 'login');
        if (! $otp) {
            return back()->withErrors(['mobile' => 'ارسال کد تأیید انجام نشد؛ لطفاً بعداً دوباره تلاش کنید.']);
        }

        $request->session()->forget('login_identified_mobile');
        $request->session()->put(['login_otp_mobile' => $validated['mobile'], 'login_otp_expires_at' => $otp->expires_at->timestamp]);

        return redirect()->route('login')->with('status', 'کد یکبار مصرف به موبایل شما ارسال شد.');
    }

    public function verifyOtp(Request $request, MobileOtpService $otpService): RedirectResponse
    {
        $request->merge(['mobile' => MobileNumber::normalizeLocal($request->input('mobile'))]);
        $validated = $request->validate(
            ['mobile' => ['required', 'regex:/^09\d{9}$/'], 'otp' => ['required', 'digits_between:5,6']],
            [
                'mobile.regex' => 'شماره موبایل باید دقیقاً ۱۱ رقم و با ۰۹ شروع شود؛ استفاده از ۹۸ یا + مجاز نیست.',
                'otp.digits_between' => 'کد تأیید باید ۵ رقم باشد.',
            ]
        );
        abort_unless($request->session()->get('login_otp_mobile') === $validated['mobile'], 422, 'درخواست OTP معتبر نیست.');

        if (! $otpService->verify($validated['mobile'], 'login', $validated['otp'])) {
            return back()->withErrors(['otp' => 'کد واردشده نادرست یا منقضی شده است.']);
        }

        $user = User::query()->where('mobile', $validated['mobile'])->where('is_active', true)->firstOrFail();
        Auth::login($user);
        $request->session()->forget(['login_otp_mobile', 'login_otp_expires_at']);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
