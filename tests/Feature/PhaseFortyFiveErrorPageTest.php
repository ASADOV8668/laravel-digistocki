<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseFortyFiveErrorPageTest extends TestCase
{
    public function test_unknown_route_uses_the_branded_persian_not_found_page(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSee('چیزی که دنبالش بودید پیدا نشد')
            ->assertSee('مشاهده آگهی‌ها')
            ->assertSee('noindex, nofollow');
    }
}
