<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneUiTest extends TestCase
{
    public function test_home_page_uses_the_digistocki_mobile_shell(): void
    {
        $this->get('/')->assertOk()->assertSee('Digistocki')->assertSee('ثبت آگهی')->assertSee('جستجوی آگهی‌ها')->assertSee('logo-header.svg')->assertSee('rel="canonical"', false)->assertSee('og:description', false);
    }

    public function test_listings_page_is_public(): void
    {
        $this->get('/listings')->assertOk()->assertSee('جستجوی آگهی‌ها');
    }

    public function test_create_listing_requires_authentication(): void
    {
        $this->get('/listings/create')->assertRedirect(route('login'));
    }
}
