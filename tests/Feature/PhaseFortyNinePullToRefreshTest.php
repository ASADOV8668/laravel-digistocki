<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseFortyNinePullToRefreshTest extends TestCase
{
    public function test_home_page_exposes_mobile_pull_to_refresh_feedback(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('pullToRefresh', false)
            ->assertSee('برای تازه‌سازی رها کنید')
            ->assertSee('در حال تازه‌سازی...', false);
    }
}
