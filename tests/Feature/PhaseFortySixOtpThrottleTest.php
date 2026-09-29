<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PhaseFortySixOtpThrottleTest extends TestCase
{
    public function test_contact_otp_endpoints_are_rate_limited(): void
    {
        $requestRoute = Route::getRoutes()->getByName('listings.contact-otp');
        $verifyRoute = Route::getRoutes()->getByName('listings.contact-otp.verify');

        $this->assertNotNull($requestRoute);
        $this->assertNotNull($verifyRoute);
        $this->assertContains('throttle:5,1', $requestRoute->middleware());
        $this->assertContains('throttle:5,1', $verifyRoute->middleware());
    }
}
