<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use App\Services\SystemOptions;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseOneHundredSixtyContactPriceTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_admin_setting_controls_contact_price_permission(): void
    {
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();

        $this->actingAs($admin)->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('اجازه ثبت «تماس بگیرید»');

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            'site_title' => 'دیجی استوک',
            'page_title_prefix' => 'دیجی استوک',
            'title_separator' => '|',
            'max_image_upload_mb' => 5,
            'allow_contact_price' => 0,
            'listings_enabled' => 1,
        ])->assertRedirect();

        $this->assertFalse(app(SystemOptions::class)->allowContactPrice());
    }

    public function test_user_can_submit_contact_price_listing_when_enabled(): void
    {
        app(SystemOptions::class)->set('allow_contact_price', true, 'boolean');
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی تماس بگیرید',
            'price_on_request' => 1,
            'attributes' => $this->requiredAttributeValues($model),
        ])->assertRedirect();

        $listing = Listing::where('title', 'آگهی تماس بگیرید')->firstOrFail();
        $this->assertNull($listing->price);
        $this->assertTrue($listing->price_on_request);
        $this->actingAs($user)->get(route('listings.show', $listing))->assertSee('تماس بگیرید');
    }

    public function test_contact_price_is_rejected_when_the_system_option_is_disabled(): void
    {
        app(SystemOptions::class)->set('allow_contact_price', false, 'boolean');
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        $this->actingAs($user)->post(route('listings.store'), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی غیرمجاز تماس',
            'price_on_request' => 1,
            'attributes' => $this->requiredAttributeValues($model),
        ])->assertSessionHasErrors(['price', 'price_on_request']);

        $this->assertDatabaseMissing('listings', ['title' => 'آگهی غیرمجاز تماس']);
    }
}
