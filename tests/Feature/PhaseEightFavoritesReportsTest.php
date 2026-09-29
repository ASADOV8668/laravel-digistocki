<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Favorite;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseEightFavoritesReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_guest_cannot_toggle_favorite_and_authenticated_user_can_toggle_it(): void
    {
        $listing = $this->listing('آگهی علاقه‌مندی');
        $user = User::factory()->create();

        $this->post(route('listings.favorite.toggle', $listing))->assertRedirect(route('login'));

        $this->actingAs($user)->postJson(route('listings.favorite.toggle', $listing))
            ->assertOk()
            ->assertJson(['favorited' => true]);
        $this->assertDatabaseHas('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);

        $this->actingAs($user)->postJson(route('listings.favorite.toggle', $listing))
            ->assertOk()
            ->assertJson(['favorited' => false]);
        $this->assertDatabaseMissing('favorites', ['user_id' => $user->id, 'listing_id' => $listing->id]);
    }

    public function test_favorites_page_only_shows_the_authenticated_users_approved_favorites(): void
    {
        $user = User::factory()->create();
        $favoriteListing = $this->listing('آگهی ذخیره‌شده');
        $otherListing = $this->listing('آگهی ذخیره‌نشده');
        $pendingListing = $this->listing('آگهی در انتظار', ListingStatus::Pending);

        Favorite::create(['user_id' => $user->id, 'listing_id' => $favoriteListing->id]);
        Favorite::create(['user_id' => User::factory()->create()->id, 'listing_id' => $otherListing->id]);
        Favorite::create(['user_id' => $user->id, 'listing_id' => $pendingListing->id]);

        $this->actingAs($user)->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($favoriteListing->title)
            ->assertDontSee($otherListing->title)
            ->assertDontSee($pendingListing->title);
    }

    public function test_user_can_report_another_users_listing_once_while_it_is_pending(): void
    {
        $listing = $this->listing('آگهی قابل گزارش');
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('listings.report', $listing), [
            'reason' => 'اطلاعات نادرست',
            'description' => 'قیمت و مشخصات با متن آگهی هم‌خوان نیست.',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseHas('reports', ['listing_id' => $listing->id, 'user_id' => $user->id, 'status' => 'pending']);
        $this->actingAs($user)->post(route('listings.report', $listing), ['reason' => 'آگهی تکراری'])
            ->assertRedirect()
            ->assertSessionHasErrors('report');
        $this->assertSame(1, Report::where('listing_id', $listing->id)->where('user_id', $user->id)->count());
    }

    private function listing(string $title, ListingStatus $status = ListingStatus::Approved): Listing
    {
        $brand = Brand::where('name_en', 'Apple')->firstOrFail();

        return Listing::create([
            'user_id' => User::factory()->create()->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => Str::uuid(),
            'price' => 32000000,
            'status' => $status,
        ]);
    }
}
