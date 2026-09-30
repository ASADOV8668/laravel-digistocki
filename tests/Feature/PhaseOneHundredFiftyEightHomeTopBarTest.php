<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseOneHundredFiftyEightHomeTopBarTest extends TestCase
{
    public function test_home_page_hides_the_redundant_back_button(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('اعلان‌ها')
            ->assertDontSee('onclick="history.back()"', false);
    }

    public function test_other_public_pages_keep_the_back_button(): void
    {
        $this->get(route('listings.index'))
            ->assertOk()
            ->assertSee('onclick="history.back()"', false);
    }

    public function test_top_bar_supports_an_explicit_back_button_flag(): void
    {
        $component = file_get_contents(resource_path('views/components/top-bar.blade.php'));

        $this->assertNotFalse($component);
        $this->assertStringContainsString("'showBack' => true", $component);
        $this->assertStringContainsString('@if ($showBack)', $component);
    }
}
