<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentySevenFlowbiteListingCardTooltipTest extends TestCase
{
    public function test_listing_card_favorite_control_uses_flowbite_tooltip_and_red_active_state(): void
    {
        $card = file_get_contents(resource_path('views/components/listing-card.blade.php'));

        $this->assertStringContainsString('data-tooltip-target="{{ $favoriteTooltipId }}"', $card);
        $this->assertStringContainsString('data-tooltip-placement="left"', $card);
        $this->assertStringContainsString('role="tooltip"', $card);
        $this->assertStringContainsString("favorited ? 'text-error'", $card);
        $this->assertStringContainsString("favorited ? 'حذف از علاقه‌مندی‌ها' : 'افزودن به علاقه‌مندی‌ها'", $card);
    }
}
