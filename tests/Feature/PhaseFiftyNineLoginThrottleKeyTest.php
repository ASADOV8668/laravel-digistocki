<?php

namespace Tests\Feature;

use App\Http\Requests\Auth\LoginRequest;
use Tests\TestCase;

class PhaseFiftyNineLoginThrottleKeyTest extends TestCase
{
    public function test_mobile_throttle_key_is_same_for_persian_and_latin_digits(): void
    {
        $latin = LoginRequest::create('/login', 'POST', ['login' => '09120000000']);
        $persian = LoginRequest::create('/login', 'POST', ['login' => '۰۹۱۲۰۰۰۰۰۰۰']);
        $latin->server->set('REMOTE_ADDR', '127.0.0.1');
        $persian->server->set('REMOTE_ADDR', '127.0.0.1');

        $this->assertSame($latin->throttleKey(), $persian->throttleKey());
    }
}
