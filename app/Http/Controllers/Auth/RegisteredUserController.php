<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SystemOptions;
use App\Support\MobileNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(SystemOptions $options): View
    {
        return view('auth.register', ['registrationMode' => $options->registrationMode()]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, SystemOptions $options): RedirectResponse
    {
        $request->merge(['mobile' => MobileNumber::normalize($request->input('mobile'))]);
        $registrationMode = $options->registrationMode();
        $emailRules = ['nullable', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class];
        $mobileRules = ['nullable', 'string', 'regex:/^09\d{9}$/', 'unique:users,mobile'];

        if ($registrationMode === 'mobile') {
            $mobileRules[] = 'required';
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'mobile' => $mobileRules,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'mobile' => $validated['mobile'] ?? null,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
