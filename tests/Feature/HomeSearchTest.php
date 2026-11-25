<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\SavedListingSearchNotification;
use App\Support\PersianDigits;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoListingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HomeSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CatalogSeeder::class, DemoListingSeeder::class]);
    }

    public function test_home_shows_latest_approved_listings_with_their_images(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('آخرین آگهی‌ها')
            ->assertSee('آیفون ۱۳ پرو تمیز و سالم')
            ->assertSee('storage/listings/demo-iphone.svg');
    }

    public function test_autocomplete_returns_matching_approved_listings(): void
    {
        $this->getJson(route('listings.autocomplete', ['q' => 'آیفون']))
            ->assertOk()
            ->assertJsonFragment(['title' => 'آیفون ۱۳ پرو تمیز و سالم'])
            ->assertJsonFragment(['image' => asset('storage/listings/demo-iphone.svg')]);
    }

    public function test_autocomplete_ignores_terms_shorter_than_two_characters(): void
    {
        $this->getJson(route('listings.autocomplete', ['q' => 'ا']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_natural_search_can_be_saved_and_sends_price_drop_notifications(): void
    {
        Notification::fake();
        $buyer = User::factory()->create();
        $query = 'آیفون ۱۳ پرو ۲۵۶ آبی';
        $listing = Listing::where('slug', 'demo-iphone-13-pro')->sole();
        $other = Listing::where('slug', 'demo-iphone-13-pro-max')->sole();

        $results = $this->actingAs($buyer)->get(route('listings.index', ['q' => $query]))->assertOk();
        $this->assertSame([$listing->id], $results->viewData('listings')->pluck('id')->all());
        $this->post(route('listings.searches.store'), ['query' => $query])->assertRedirect(route('listings.searches.index'));
        $this->get(route('listings.searches.index'))->assertOk()->assertSeeText(PersianDigits::toEnglish($query));

        $listing->update(['price' => $listing->price - 1000000]);
        Notification::assertSentTo($buyer, SavedListingSearchNotification::class);
        $this->get(route('home'))->assertOk()->assertSeeText('کاهش قیمت');
        $this->get(route('listings.index', ['compare' => [$listing->id, $other->id]]))
            ->assertOk()->assertSeeText('مقایسهٔ آگهی‌ها')->assertSeeText($other->title);
    }

    public function test_model_attribute_endpoint_returns_available_storage_and_color_values(): void
    {
        $model = PhoneModel::whereHas('brand', fn ($query) => $query->where('name_en', 'Apple'))->where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $storage = Attribute::where('slug', 'storage')->firstOrFail();
        $color = Attribute::where('slug', 'color')->firstOrFail();

        $this->getJson(route('listings.models.attributes', $model))
            ->assertOk()
            ->assertJsonPath('0.slug', 'storage')
            ->assertJsonPath('0.values', [256])
            ->assertJsonPath('2.slug', 'color')
            ->assertJsonPath('2.values', ['طلایی']);

        $this->assertDatabaseHas('listing_attribute_values', ['attribute_id' => $storage->id, 'value_integer' => 256]);
        $this->assertDatabaseHas('listing_attribute_values', ['attribute_id' => $color->id, 'value_string' => 'طلایی']);
    }

    public function test_home_search_ui_exposes_model_storage_and_color_steps(): void
    {
        $view = file_get_contents(resource_path('views/welcome.blade.php'));

        $this->assertStringContainsString("url('/listings/models')", $view);
        $this->assertStringContainsString('quickAttributes()', $view);
        $this->assertStringContainsString('selectQuickAttribute(', $view);
        $this->assertStringContainsString('quickStep', $view);
        $this->assertStringContainsString('filters[${attribute.id}]', $view);
    }

    public function test_chained_search_reads_legacy_numeric_values_after_attribute_type_change(): void
    {
        $model = PhoneModel::where('name_en', 'iPhone 13 Pro Max')->firstOrFail();
        $storage = Attribute::where('slug', 'storage')->firstOrFail();

        // Existing production data may still use value_integer after an admin
        // changes the catalog field from integer to select.
        $storage->update(['type' => AttributeType::Select]);

        $this->getJson(route('listings.models.attributes', $model))
            ->assertOk()
            ->assertJsonPath('0.slug', 'storage')
            ->assertJsonPath('0.values', [256]);

        $response = $this->get(route('listings.index', [
            'phone_model_id' => $model->id,
            'filters' => [$storage->id => 256],
        ]));

        $response->assertOk();
        $this->assertTrue($response->viewData('listings')->getCollection()->contains('title', 'آیفون ۱۳ پرو مکس با حافظه ۲۵۶'));
    }
}
