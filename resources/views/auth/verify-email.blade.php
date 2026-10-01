<x-guest-layout>
    <div class="mb-4 text-sm leading-7 text-slate-500">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <x-flowbite-alert type="success" class="mb-4">{{ __('A new verification link has been sent to the email address you provided during registration.') }}</x-flowbite-alert>
    @endif

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button class="w-full sm:w-auto">
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <x-secondary-button type="submit" class="text-sm">
                {{ __('Log Out') }}
            </x-secondary-button>
        </form>
    </div>
</x-guest-layout>
