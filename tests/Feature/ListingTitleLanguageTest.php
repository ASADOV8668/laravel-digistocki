<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Services\SystemOptions;
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

        app(SystemOptions::class)->set('listing_title_language', 'en');

        $english = ListingTitle::generate($brand->id, $model->id, [
            $color->id => 'آبی',
            $storage->id => '۲۵۶',
        ]);

        $this->assertSame('Apple iPhone 13 Pro Max Blue 256 GB', $english);
        $this->assertTrue(ListingTitle::isEnglishTitle($english));

        app(SystemOptions::class)->set('listing_title_language', 'fa');

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

    public function test_part_number_comes_between_storage_and_battery_in_generated_and_custom_titles(): void
    {
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();
        $model = $brand->phoneModels()->where('name_en', 'iPhone 16 Pro Max')->firstOrFail();
        $part = Attribute::create(['slug' => 'part_number', 'name' => 'پارت نامبر', 'type' => 'string', 'is_active' => true]);
        $model->attributes()->attach($part->id, ['is_required' => false]);
        $values = [
            Attribute::where('slug', 'storage')->value('id') => '۲۵۶',
            $part->id => 'zaa',
            Attribute::where('slug', 'battery_health')->value('id') => '۹۸',
        ];

        $this->assertSame('Apple iPhone 16 Pro Max 256GB ZAA 98%', ListingTitle::generate($brand->id, $model->id, $values, 'en'));
        $this->assertSame('iPhone 16 Promax 256GB ZAA 98%', ListingTitle::generateForCamera($brand->id, $model->id, $values));
        $this->assertSame('iPhone 16 Pro Max 256GB ZAA 98% Black', ListingTitle::alignSubmittedTitle('iPhone 16 Pro Max 98% 256 GB CH Black', $values, [$part->id => 'CH']));
        $this->assertSame('آیفون ۱۶ پرو مکس ۲۵۶ گیگابایت ZAA ۹۸٪', ListingTitle::alignSubmittedTitle('آیفون ۱۶ پرو مکس ۲۵۶ گیگابایت ۹۸٪ zaa', $values));

        $listing = Listing::create([
            'user_id' => User::factory()->create()->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id,
            'title' => 'iPhone 16 Pro Max 98% 256 GB ZAA Black', 'slug' => 'existing-part-number-order', 'status' => 'pending',
        ]);
        $listing->attributeValues()->create(['attribute_id' => $part->id, 'value_string' => 'zaa']);
        $migration = require database_path('migrations/2026_11_25_000520_align_existing_listing_part_numbers.php');
        $migration->up();
        $this->assertSame('iPhone 16 Pro Max 256GB ZAA 98% Black', $listing->refresh()->title);
        $this->assertSame('existing-part-number-order', $listing->slug);

        $listing->update(['status' => 'approved']);
        $listing->attributeValues()->create(['attribute_id' => Attribute::where('slug', 'color')->value('id'), 'value_string' => 'مشکی']);
        $migration = require database_path('migrations/2026_11_26_000300_remove_color_from_published_listing_titles.php');
        $migration->up();
        $this->assertSame('iPhone 16 Pro Max 256GB ZAA 98%', $listing->refresh()->title);
        $this->assertSame('existing-part-number-order', $listing->slug);
        $this->assertSame('Apple iPhone 13 Pro Max 256 GB', ListingTitle::withoutColor('Apple iPhone 13 Pro Max Blue 256 GB', 'آبی'));
        $this->assertSame('Black Shark 5 Pro 256 GB', ListingTitle::withoutColor('Black Shark 5 Pro 256 GB', 'Black'));
    }
}
