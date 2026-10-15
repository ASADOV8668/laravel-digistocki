<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $firstPasswordSetup = $request->boolean('password_setup_available')
            || $request->session()->get('password_setup_available', false) === true
            || ! $request->user()->hasSetPassword();
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => $firstPasswordSetup ? ['nullable'] : ['required', 'current_password'],
            'password' => ['required', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
        ], [
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.mixed' => 'رمز عبور باید شامل حروف بزرگ و کوچک انگلیسی باشد.',
            'password.numbers' => 'رمز عبور باید حداقل شامل یک عدد باشد.',
            'password.confirmed' => 'تکرار رمز عبور صحیح نیست.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'password_set_at' => now(),
        ]);
        $request->session()->forget('password_setup_available');

        return back()->with('status', 'password-updated');
    }
}
