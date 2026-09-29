<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseSeventyNotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_deleted_listing_notification_does_not_render_a_broken_link(): void
    {
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی اعلان حذف‌شده',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);

        $owner->notify(new ListingStatusNotification($listing, 'approved'));
        $listing->delete();

        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('آگهی دیگر در دسترس نیست')
            ->assertDontSee('مشاهده آگهی');
    }
}
