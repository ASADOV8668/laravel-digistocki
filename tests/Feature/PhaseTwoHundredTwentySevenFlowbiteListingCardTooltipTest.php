<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentySevenFlowbiteListingCardTooltipTest extends TestCase
{
    public function test_listing_card_favorite_control_has_no_tooltip_and_guest_toast_hook(): void
    {
        $card = file_get_contents(resource_path('views/components/listing-card.blade.php'));

        $this->assertStringNotContainsString('data-tooltip-target=', $card);
        $this->assertStringNotContainsString('favoriteTooltipId', $card);
        $this->assertStringContainsString('@click.stop.prevent="toggle"', $card);
        $this->assertStringContainsString("favorited ? 'text-error'", $card);
        $this->assertStringContainsString("favorited ? 'حذف آگهی از علاقه‌مندی‌ها' : 'افزودن آگهی به علاقه‌مندی‌ها'", $card);
        $this->assertStringContainsString('برای افزودن آگهی به علاقه‌مندی‌ها باید وارد سایت شوید.', file_get_contents(resource_path('js/app.js')));
    }
}
