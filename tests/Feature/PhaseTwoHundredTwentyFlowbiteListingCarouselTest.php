<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredTwentyFlowbiteListingCarouselTest extends TestCase
{
    public function test_listing_gallery_uses_flowbite_carousel_controls(): void
    {
        $source = file_get_contents(resource_path('views/listings/show.blade.php'));

        $this->assertStringContainsString('data-carousel="static"', $source);
        $this->assertStringContainsString('data-carousel-interval="false"', $source);
        $this->assertStringContainsString('data-listing-carousel', $source);
        $this->assertStringContainsString('listing-gallery-modal', $source);
        $this->assertStringContainsString('data-modal-toggle="listing-gallery-modal"', $source);
        $this->assertStringContainsString('data-carousel-item', $source);
        $this->assertStringContainsString('data-carousel-prev', $source);
        $this->assertStringContainsString('data-carousel-next', $source);
        $this->assertStringContainsString('data-carousel-slide-to', $source);
        $this->assertStringContainsString('data-gallery-thumbnail', $source);
        $this->assertStringContainsString('aspect-[4/3]', $source);
        $this->assertStringContainsString('object-contain', $source);
        $this->assertStringContainsString('z-40', $source);
        $this->assertStringContainsString('loading="lazy"', $source);
        $this->assertStringContainsString('initListingCarouselGestures', file_get_contents(resource_path('js/app.js')));
    }
}
