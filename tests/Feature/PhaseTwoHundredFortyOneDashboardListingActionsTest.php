<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\ListingAttributeValue;
use App\Models\SellerStore;
use App\Models\User;
use App\Support\PersianDate;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class PhaseTwoHundredFortyOneDashboardListingActionsTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_owner_can_quick_update_price_and_disable_listing(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی سریع', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)->patch(route('listings.quick-update', $listing), ['price' => 2500000])->assertRedirect();
        $this->actingAs($user)->patch(route('listings.toggle-disabled', $listing))->assertRedirect();
        $this->assertSame(2500000, $listing->refresh()->price);
        $this->assertTrue($listing->is_user_disabled);
    }

    public function test_dashboard_quick_price_forms_group_digits_and_submit_a_raw_price(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->first()->id,
            'title' => 'فرم قیمت سریع',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('x-data="moneyInput(', false)
            ->assertSee('x-model="display"', false)
            ->assertSee('type="hidden" name="price"', false)
            ->assertSee('data-listing-price', false)
            ->assertSee('data-listing-card="'.$listing->id.'"', false)
            ->assertSee('data-quick-update-listing="'.$listing->id.'"', false)
            ->assertSee('type="hidden" name="bulk_price"', false);
    }

    public function test_dashboard_allows_changing_page_size_up_to_five_hundred(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        foreach (range(1, 12) as $number) {
            Listing::create([
                'user_id' => $user->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'آگهی تعداد '.$number,
                'slug' => Str::uuid(),
                'price' => 1000000,
                'status' => ListingStatus::Approved,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard', ['per_page' => '۵۰۰']));

        $response->assertOk()
            ->assertSee('id="dashboard-per-page"', false)
            ->assertSee('value="500"', false)
            ->assertSee('۵۰۰ آگهی');
        $this->assertSame(500, $response->viewData('listings')->perPage());
        $this->assertCount(12, $response->viewData('listings')->getCollection());
    }

    public function test_quick_dashboard_actions_return_json_for_ajax_requests(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی آجاکس', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)
            ->patchJson(route('listings.quick-update', $listing), ['price' => '۲۷۵۰۰۰۰'])
            ->assertOk()
            ->assertJson(['price' => 2750000, 'price_on_request' => false]);

        $this->actingAs($user)
            ->patchJson(route('listings.toggle-disabled', $listing))
            ->assertOk()
            ->assertJson(['is_user_disabled' => true]);
    }

    public function test_bulk_dashboard_actions_return_json_and_update_selected_listings(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $attributes = ['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'slug' => Str::uuid(), 'status' => ListingStatus::Approved];
        $first = Listing::create(array_merge($attributes, ['title' => 'گروهی اول', 'price' => 1000000]));
        $second = Listing::create(array_merge($attributes, ['title' => 'گروهی دوم', 'price' => 2000000, 'slug' => Str::uuid()]));

        $this->actingAs($user)
            ->postJson(route('dashboard.listings.bulk'), [
                'action' => 'price',
                'bulk_price' => '۳۲۰۰۰۰۰',
                'listing_ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['action' => 'price', 'price' => 3200000]);

        $this->actingAs($user)
            ->postJson(route('dashboard.listings.bulk'), [
                'action' => 'disable',
                'listing_ids' => [$first->id, $second->id],
            ])
            ->assertOk()
            ->assertJson(['action' => 'disable']);

        $this->assertSame(3200000, $first->refresh()->price);
        $this->assertTrue($first->is_user_disabled);
        $this->assertSame(3200000, $second->refresh()->price);
        $this->assertTrue($second->is_user_disabled);
    }

    public function test_bulk_sold_marks_only_published_listings_as_sold(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $approved = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی فروش گروهی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);
        $pending = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی در انتظار گروهی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Pending]);

        $this->actingAs($user)
            ->postJson(route('dashboard.listings.bulk'), [
                'action' => 'sold',
                'listing_ids' => [$approved->id, $pending->id],
            ])
            ->assertOk()
            ->assertJson(['action' => 'sold', 'listing_ids' => [$approved->id]])
            ->assertJsonPath('message', 'آگهی‌های منتشرشده انتخاب‌شده به‌عنوان فروخته‌شده علامت خوردند.');

        $this->assertSame(ListingStatus::Sold, $approved->refresh()->status);
        $this->assertSame(ListingStatus::Pending, $pending->refresh()->status);
    }

    public function test_quick_update_requires_a_price_when_contact_mode_is_not_selected(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'قیمت الزامی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)
            ->patch(route('listings.quick-update', $listing), ['price' => ''])
            ->assertSessionHasErrors('price');

        $this->assertSame(1000000, $listing->refresh()->price);
        $this->assertFalse($listing->price_on_request);
    }

    public function test_dashboard_clone_page_is_prefilled_from_the_owner_listing(): void
    {
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'clone-store-'.$user->id, 'name' => 'فروشگاه کپی']);
        $brand = Brand::firstOrFail();
        $listing = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی قابل کپی', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);

        $this->actingAs($user)->get(route('listings.create', ['clone' => $listing->id]))
            ->assertOk()
            ->assertSee('آگهی قابل کپی (کپی)')
            ->assertSee('name="clone"', false);
    }

    public function test_dashboard_clone_page_prefills_the_source_listing_attributes(): void
    {
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'clone-attributes-'.$user->id, 'name' => 'فروشگاه ویژگی کپی']);
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $memory = $model->attributes()->where('slug', 'storage')->first() ?? $model->attributes()->where('type', 'integer')->firstOrFail();
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی با ویژگی قابل کپی',
            'slug' => Str::uuid(),
            'price' => 1000000,
            'status' => ListingStatus::Approved,
        ]);
        ListingAttributeValue::create([
            'listing_id' => $listing->id,
            'attribute_id' => $memory->id,
            'value_integer' => 256,
        ]);

        $this->actingAs($user)->get(route('listings.create', ['clone' => $listing->id]))
            ->assertOk()
            ->assertSee('data-listing-initial-values', false)
            ->assertSee('"'.$memory->id.'":256', false);
    }

    public function test_dashboard_filters_by_persian_date_and_supports_bulk_price_actions(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $insideDate = PersianDate::parseDate('1404/10/10');
        $outsideDate = PersianDate::parseDate('1404/09/30');
        $inside = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی در بازه', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);
        $inside->forceFill(['created_at' => $insideDate])->save();
        $outside = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $brand->phoneModels()->first()->id, 'title' => 'آگهی خارج بازه', 'slug' => Str::uuid(), 'price' => 2000000, 'status' => ListingStatus::Approved]);
        $outside->forceFill(['created_at' => $outsideDate])->save();

        $this->actingAs($user)->get(route('dashboard', [
            'from_date_year' => '1404', 'from_date_month' => '10', 'from_date_day' => '1',
            'to_date_year' => '1404', 'to_date_month' => '10', 'to_date_day' => '30',
        ]))
            ->assertOk()
            ->assertSee('آگهی در بازه')
            ->assertDontSee('آگهی خارج بازه')
            ->assertSee('name="from_date_year"', false)
            ->assertSee('bulk-price-modal', false);

        $this->actingAs($user)->get(route('dashboard', [
            'from_date_year' => '1404', 'from_date_month' => '11', 'from_date_day' => '1',
            'to_date_year' => '1404', 'to_date_month' => '10', 'to_date_day' => '1',
        ]))->assertOk()->assertSee('بازه تاریخ معتبر نیست');

        $this->actingAs($user)->post(route('dashboard.listings.bulk'), [
            'action' => 'price',
            'bulk_price' => '3500000',
            'listing_ids' => [$inside->id, $outside->id],
        ])->assertRedirect();
        $this->assertSame(3500000, $inside->refresh()->price);
        $this->assertSame(3500000, $outside->refresh()->price);

        $this->actingAs($user)->post(route('dashboard.listings.bulk'), [
            'action' => 'contact_price',
            'listing_ids' => [$inside->id, $outside->id],
        ])->assertRedirect();
        $this->assertTrue($inside->refresh()->price_on_request);
        $this->assertNull($inside->price);
        $this->assertTrue($outside->refresh()->price_on_request);
        $this->assertNull($outside->price);
    }

    public function test_dashboard_can_select_all_filtered_listings_across_pages(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();

        foreach (range(1, 11) as $number) {
            Listing::create([
                'user_id' => $user->id,
                'brand_id' => $brand->id,
                'phone_model_id' => $model->id,
                'title' => 'نتیجه گروهی '.$number,
                'slug' => Str::uuid(),
                'price' => 1000000,
                'status' => ListingStatus::Approved,
            ]);
        }

        $response = $this->actingAs($user)->get(route('dashboard', ['q' => 'نتیجه گروهی']));

        $response->assertOk()
            ->assertSee('انتخاب همه نتایج فیلترشده (۱۱ مورد)', false)
            ->assertSee('dashboardBulkActions', false);
    }

    public function test_dashboard_filters_by_a_selected_model_attribute(): void
    {
        $user = User::factory()->create();
        $brand = Brand::firstOrFail();
        $model = $brand->phoneModels()->firstOrFail();
        $memory = $model->attributes()->where('slug', 'storage')->first() ?? $model->attributes()->where('type', 'integer')->firstOrFail();
        $matching = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی حافظه ۲۵۶', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);
        $other = Listing::create(['user_id' => $user->id, 'brand_id' => $brand->id, 'phone_model_id' => $model->id, 'title' => 'آگهی حافظه ۱۲۸', 'slug' => Str::uuid(), 'price' => 1000000, 'status' => ListingStatus::Approved]);
        ListingAttributeValue::create(['listing_id' => $matching->id, 'attribute_id' => $memory->id, 'value_integer' => 256]);
        ListingAttributeValue::create(['listing_id' => $other->id, 'attribute_id' => $memory->id, 'value_integer' => 128]);

        $this->actingAs($user)->get(route('dashboard', [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'filters' => [$memory->id => '256'],
        ]))->assertOk()->assertSee('آگهی حافظه ۲۵۶')->assertDontSee('آگهی حافظه ۱۲۸')->assertSee('فیلتر ویژگی‌های مدل');
    }
}
