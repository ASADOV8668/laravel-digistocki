<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Support\ListingTitle;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingTitleLanguageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_title_uses_the_configured_language(): void
    {
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $color = Attribute::query()->where('slug', 'color')->firstOrFail();
        $storage = Attribute::query()->where('slug', 'storage')->firstOrFail();

        app(\App\Services\SystemOptions::class)->set('listing_title_language', 'en');

        $english = ListingTitle::generate($brand->id, $model->id, [
            $color->id => 'آبی',
            $storage->id => '۲۵۶',
        ]);

        $this->assertSame('Apple iPhone 13 Pro Max Blue 256 GB', $english);
        $this->assertTrue(ListingTitle::isEnglishTitle($english));

        app(\App\Services\SystemOptions::class)->set('listing_title_language', 'fa');

        $persian = ListingTitle::generate($brand->id, $model->id, [
            $color->id => 'آبی',
            $storage->id => 256,
        ]);

        $this->assertSame('اپل آیفون ۱۳ پرو مکس آبی ۲۵۶ گیگابایت', $persian);
        $this->assertFalse(ListingTitle::isEnglishTitle($persian));
        $this->assertSame('Memory', ListingTitle::attributeName('storage', 'حافظه داخلی', true));
        $this->assertSame('تومان', ListingTitle::priceUnit(true));
    }

    public function test_admin_listing_form_reenables_automatic_titles_after_model_changes(): void
    {
        $form = file_get_contents(resource_path('views/admin/listings/form.blade.php'));
        $script = file_get_contents(resource_path('js/admin-listing-form.js'));

        $this->assertStringContainsString('@change="markTitleAutomatic(); loadModels()"', $form);
        $this->assertStringContainsString('@change="markTitleAutomatic(); loadAttributes"', $form);
        $this->assertStringContainsString('markTitleAutomatic()', $script);
        $this->assertStringContainsString('this.titleInitialized', $script);
    }
}
