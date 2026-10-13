<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredEightyTwoListingPresentationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_listing_form_has_a_grouped_price_input_and_raw_hidden_value(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = $this->listing(User::factory()->create());

        $this->actingAs($admin)->get(route('admin.listings.edit', $listing))
            ->assertOk()
            ->assertSee('x-data="moneyInput(', false)
            ->assertSee('x-model="display"', false)
            ->assertSee('type="hidden" name="price"', false);
    }

    public function test_admin_listing_status_labels_are_kept_on_one_line(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $listing = $this->listing(User::factory()->create());

        $this->actingAs($admin)->get(route('admin.listings.index'))
            ->assertOk()
            ->assertSee('inline-flex whitespace-nowrap', false)
            ->assertSee('block whitespace-nowrap text-[11px]', false);
    }

    private function listing(User $user): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی تست نمایش',
            'slug' => Str::uuid(),
            'price' => 32000000,
            'status' => ListingStatus::Pending,
        ]);
    }
}
