<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\Report;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseOneHundredTwentySevenAtomicReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_pending_report_is_not_duplicated(): void
    {
        $this->seed(CatalogSeeder::class);
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی گزارش اتمیک',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
        Report::create(['listing_id' => $listing->id, 'user_id' => $reporter->id, 'reason' => 'گزارش قبلی', 'status' => 'pending']);

        $this->actingAs($reporter)
            ->post(route('listings.report', $listing), ['reason' => 'گزارش تکراری'])
            ->assertSessionHasErrors('report');

        $this->assertSame(1, Report::query()->where('listing_id', $listing->id)->where('user_id', $reporter->id)->count());
    }
}
