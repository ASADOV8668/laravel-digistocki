<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseThirteenListingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_expire_command_marks_only_due_approved_listings(): void
    {
        [$user, $brand, $model] = $this->context();
        $due = $this->listing($user, $brand, $model, 'آگهی منقضی', ListingStatus::Approved, now()->subMinute());
        $future = $this->listing($user, $brand, $model, 'آگهی فعال', ListingStatus::Approved, now()->addDay());
        $pending = $this->listing($user, $brand, $model, 'آگهی در انتظار', ListingStatus::Pending, now()->subDay());

        $this->artisan('listings:expire')->expectsOutput('1 آگهی منقضی شد.')->assertExitCode(0);

        $this->assertSame(ListingStatus::Expired, $due->refresh()->status);
        $this->assertSame(ListingStatus::Approved, $future->refresh()->status);
        $this->assertSame(ListingStatus::Pending, $pending->refresh()->status);
    }

    public function test_expired_listings_are_hidden_from_public_pages_and_favorites(): void
    {
        [$user, $brand, $model] = $this->context();
        $expired = $this->listing($user, $brand, $model, 'آگهی تاریخ گذشته', ListingStatus::Approved, now()->subMinute());

        $this->get(route('listings.index'))->assertOk()->assertDontSee($expired->title);
        $this->get(route('home'))->assertOk()->assertDontSee($expired->title);
        $this->get(route('listings.show', $expired))->assertForbidden();
    }

    public function test_owner_can_resubmit_an_expired_listing_for_moderation(): void
    {
        [$user, $brand, $model] = $this->context();
        $expired = $this->listing($user, $brand, $model, 'آگهی قابل تمدید', ListingStatus::Expired, now()->subDay());

        $this->actingAs($user)->patch(route('listings.renew', $expired))->assertRedirect();

        $this->assertSame(ListingStatus::Pending, $expired->refresh()->status);
        $this->assertNull($expired->fresh()->expires_at);
    }

    private function context(): array
    {
        $brand = Brand::firstOrFail();

        return [User::factory()->create(), $brand, $brand->phoneModels()->firstOrFail()];
    }

    private function listing(User $user, Brand $brand, PhoneModel $model, string $title, ListingStatus $status, $expiresAt): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => $title,
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
            'expires_at' => $expiresAt,
        ]);
    }
}
