<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationTopBarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_home_top_bar_shows_unread_count_and_hides_back_button(): void
    {
        $user = User::factory()->create();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => Brand::firstOrFail()->id,
            'phone_model_id' => PhoneModel::firstOrFail()->id,
            'title' => 'آگهی نوتیفیکیشن '.Str::random(5),
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => 'approved',
        ]);
        $user->notify(new ListingStatusNotification($listing, 'approved'));

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk()
            ->assertSee('اعلان‌ها', false)
            ->assertSee('اعلان خوانده‌نشده', false)
            ->assertSee('1', false)
            ->assertDontSee('aria-label="بازگشت"', false);
    }

    public function test_inner_public_pages_show_back_button_without_notification_action(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('support.index'));

        $response->assertOk()
            ->assertSee('aria-label="بازگشت"', false)
            ->assertDontSee('data-tooltip-target="public-notifications-tooltip"', false);
    }
}
