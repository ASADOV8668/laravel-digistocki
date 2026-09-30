<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredFortyAdminListingEditLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_listing_feed_links_each_title_to_editor(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $listing = Listing::create(['user_id' => $owner->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'لینک ویرایش', 'slug' => 'edit-link', 'price' => 1000, 'status' => 'pending']);

        $this->actingAs($admin)->get(route('admin.listings.index'))
            ->assertOk()->assertSee(route('admin.listings.edit', $listing), false)->assertSee('لینک ویرایش');
    }
}
