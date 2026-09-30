<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftySixPwaRegistrationCoverageTest extends TestCase
{
    public function test_guest_layout_registers_the_service_worker(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee("navigator.serviceWorker.register('".asset('sw.js')."')", false)
            ->assertSee('.then((registration) => registration.update())', false);
    }

    public function test_public_and_guest_layouts_use_the_same_service_worker_asset(): void
    {
        $publicLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $guestLayout = file_get_contents(resource_path('views/layouts/guest.blade.php'));

        $this->assertNotFalse($publicLayout);
        $this->assertNotFalse($guestLayout);
        $this->assertSame(substr_count($publicLayout, "navigator.serviceWorker.register('{{ asset('sw.js') }}')"), 1);
        $this->assertSame(substr_count($guestLayout, "navigator.serviceWorker.register('{{ asset('sw.js') }}')"), 1);
    }
}
