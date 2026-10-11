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
            || $request->session()->get('password_setup_available', false) === true;
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => $firstPasswordSetup ? ['nullable'] : ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);
        $request->session()->forget('password_setup_available');

        return back()->with('status', 'password-updated');
    }
}
