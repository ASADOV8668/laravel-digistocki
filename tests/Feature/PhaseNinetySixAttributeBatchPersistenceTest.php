<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseNinetySixAttributeBatchPersistenceTest extends TestCase
{
    use RefreshDatabase;
    use BuildsListingAttributePayload;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_attributes_are_inserted_in_one_batch(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $memory = Attribute::where('name', 'حافظه داخلی')->firstOrFail();
        $payload = [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی batch ویژگی‌ها',
            'price' => 22000000,
            'attributes' => array_replace($this->requiredAttributeValues($model), [$memory->id => 256]),
        ];

        DB::enableQueryLog();
        $this->actingAs($user)->post(route('listings.store'), $payload)->assertRedirect();
        $insertQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'insert into `listing_attribute_values`'));
        DB::disableQueryLog();

        $listing = Listing::where('title', 'آگهی batch ویژگی‌ها')->firstOrFail();
        $this->assertSame(1, $insertQueries->count());
        $this->assertGreaterThan(1, $listing->attributeValues()->count());
    }
}
