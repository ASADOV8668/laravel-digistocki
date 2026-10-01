<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Services\SystemOptions;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredThirtySixDuplicateListingFeedbackTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_duplicate_listing_returns_to_form_with_a_friendly_error(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی قبلی',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Pending,
        ]);

        app(SystemOptions::class)->set('listing_duplicate_cooldown_hours', 48, 'integer');

        $this->actingAs($user)
            ->from(route('listings.create'))
            ->post(route('listings.store'), [
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی تکراری',
                'price' => 2000000,
                'attributes' => $this->requiredAttributeValues($model),
            ])
            ->assertRedirect(route('listings.create'))
            ->assertSessionHasErrors(['phone_model_id' => 'برای این مدل در ۴۸ ساعت گذشته آگهی ثبت کرده‌اید.']);

        $this->assertDatabaseMissing('listings', ['title' => 'آگهی تکراری']);
    }
}
