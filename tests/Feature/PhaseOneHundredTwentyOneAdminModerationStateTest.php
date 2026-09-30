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

class PhaseOneHundredTwentyOneAdminModerationStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sold_and_expired_listings_cannot_reenter_moderation(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::firstOrFail();
        $sold = $this->listing(ListingStatus::Sold, 'آگهی فروخته‌شده');
        $expired = $this->listing(ListingStatus::Expired, 'آگهی منقضی');

        $this->actingAs($admin)->patch(route('admin.listings.approve', $sold))->assertStatus(422);
        $this->actingAs($admin)->patch(route('admin.listings.reject', $sold), ['rejection_reason' => 'تغییر وضعیت نامعتبر'])->assertStatus(422);
        $this->actingAs($admin)->patch(route('admin.listings.approve', $expired))->assertStatus(422);
        $this->actingAs($admin)->patch(route('admin.listings.reject', $expired), ['rejection_reason' => 'تغییر وضعیت نامعتبر'])->assertStatus(422);

        $this->assertSame(ListingStatus::Sold, $sold->refresh()->status);
        $this->assertSame(ListingStatus::Expired, $expired->refresh()->status);

        $this->actingAs($admin)
            ->get(route('admin.listings.index', ['status' => 'expired']))
            ->assertOk()
            ->assertDontSee(route('admin.listings.approve', $expired), false)
            ->assertDontSee(route('admin.listings.reject', $expired), false);
    }

    private function listing(ListingStatus $status, string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 18000000,
            'status' => $status,
        ]);
    }
}
