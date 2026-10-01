<?php

namespace Tests\Feature;

use Tests\TestCase;

class PhaseTwoHundredThirtyFourListingDemoGalleryTest extends TestCase
{
    public function test_demo_iphone_seed_has_three_gallery_images_and_listing_attributes_use_persian_digits(): void
    {
        $seeder = file_get_contents(base_path('database/seeders/DemoListingSeeder.php'));
        $showView = file_get_contents(resource_path('views/listings/show.blade.php'));

        $this->assertIsString($seeder);
        $this->assertIsString($showView);
        $this->assertStringContainsString('demo-iphone-front.svg', $seeder);
        $this->assertStringContainsString('demo-iphone-back.svg', $seeder);
        $this->assertStringContainsString('demo-iphone-side.svg', $seeder);
        $this->assertStringContainsString('ListingImage::create', $seeder);
        $this->assertStringContainsString('PersianNumber::digits($displayValue)', $showView);
    }
}
