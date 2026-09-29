<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSixtyFourAutocompleteRelationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_autocomplete_does_not_query_unused_attribute_values(): void
    {
        $brand = Brand::firstOrFail();
        Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی autocomplete سبک',
            'slug' => Str::uuid(),
            'price' => 12000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        DB::enableQueryLog();
        $this->getJson(route('listings.autocomplete', ['q' => 'autocomplete']))->assertOk();

        $this->assertFalse(collect(DB::getQueryLog())->contains(
            fn (array $query): bool => str_contains($query['query'], 'listing_attribute_values')
        ));
        DB::disableQueryLog();
    }
}
