<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\User;
use App\Support\PersianDate;
use Carbon\Carbon;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseTwoHundredOneListingCardInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_listing_cards_render_favorite_state_for_guest_and_authenticated_viewers(): void
    {
        $listing = $this->listing('کارت علاقه‌مندی');
        $user = User::factory()->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('favoriteToggle', false);
        $this->assertStringContainsString('ورود لازم است', file_get_contents(resource_path('js/app.js')));

        Favorite::create(['user_id' => $user->id, 'listing_id' => $listing->id]);

        $response = $this->actingAs($user)->get(route('listings.index'))
            ->assertOk()
            ->assertSee('حذف آگهی از علاقه‌مندی‌ها', false)
            ->assertSee('تصویر');
        $this->assertStringNotContainsString('آگهی فعال', (string) $response->getContent());
    }

    public function test_admin_listing_date_filter_converts_jalali_input_to_gregorian_query(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $included = $this->listing('آگهی در تاریخ انتخابی');
        $excluded = $this->listing('آگهی خارج از تاریخ انتخابی');
        $includedDate = Carbon::create(2026, 9, 23, 12);
        $excludedDate = Carbon::create(2026, 9, 22, 12);
        $included->forceFill(['created_at' => $includedDate, 'updated_at' => $includedDate])->saveQuietly();
        $excluded->forceFill(['created_at' => $excludedDate, 'updated_at' => $excludedDate])->saveQuietly();

        $this->actingAs($admin)->get(route('admin.listings.index', [
            'date_from' => PersianDate::format($includedDate),
            'date_to' => PersianDate::format($includedDate),
        ]))->assertOk()->assertSee($included->title)->assertDontSee($excluded->title);
    }

    private function listing(string $title): Listing
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();

        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 32000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
