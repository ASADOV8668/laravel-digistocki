<?php

namespace Tests\Feature;

use App\Models\SellerStore;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseOneHundredSixtyFiveStorefrontSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_enabled_storefronts_are_added_to_the_sitemap(): void
    {
        $active = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'indexable-shop',
            'name' => 'غرفه قابل ایندکس',
            'is_enabled' => true,
        ]);
        $disabled = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'no-index-shop',
            'name' => 'غرفه غیرقابل ایندکس',
            'is_enabled' => false,
        ]);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee(route('storefront.show', $active), false)
            ->assertDontSee(route('storefront.show', $disabled), false);
    }

    public function test_active_storefront_exposes_local_business_structured_data(): void
    {
        $store = SellerStore::create([
            'user_id' => User::factory()->create()->id,
            'slug' => 'seo-shop',
            'name' => 'غرفه سئو',
            'is_enabled' => true,
            'contact_phone' => '09120006666',
        ]);

        $this->get(route('storefront.show', $store))
            ->assertOk()
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"LocalBusiness"', false)
            ->assertSee($store->name)
            ->assertSee($store->contact_phone);
    }
}
