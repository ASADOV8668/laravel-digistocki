<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyFlowbiteListingCarouselTest extends TestCase
{
    public function test_listing_gallery_uses_flowbite_carousel_controls(): void
    {
        $source = file_get_contents(resource_path('views/listings/show.blade.php'));

        $this->assertStringContainsString('data-carousel="slide"', $source);
        $this->assertStringContainsString('data-carousel-item', $source);
        $this->assertStringContainsString('data-carousel-prev', $source);
        $this->assertStringContainsString('data-carousel-next', $source);
        $this->assertStringContainsString('data-carousel-slide-to', $source);
        $this->assertStringContainsString('loading="lazy"', $source);
    }
}
