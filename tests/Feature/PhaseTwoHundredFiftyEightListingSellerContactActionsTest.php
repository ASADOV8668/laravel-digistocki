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

class PhaseTwoHundredFiftyEightListingSellerContactActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_authenticated_viewer_sees_phone_sms_and_chat_actions(): void
    {
        $seller = User::factory()->create(['mobile' => '09120000000']);
        $viewer = User::factory()->create(['mobile' => '09121111111']);
        $listing = $this->listing($seller, 'آگهی تست ارتباط با فروشنده');
        $message = "دیجی استوکی ، بابت آگهی {$listing->title} نیاز به ارتباط با شما دارم.";

        $this->actingAs($viewer)
            ->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('تماس تلفنی')
            ->assertSee('پیامک')
            ->assertSee('چت با فروشنده')
            ->assertSee('09120000000')
            ->assertSee('tel:09120000000', false)
            ->assertSee('sms:09120000000?body=' . rawurlencode($message), false)
            ->assertSee('به‌زودی');
    }

    public function test_guest_does_not_receive_seller_contact_action_links(): void
    {
        $seller = User::factory()->create(['mobile' => '09120000000']);
        $listing = $this->listing($seller, 'آگهی خصوصی تماس');

        $this->get(route('listings.show', $listing))
            ->assertOk()
            ->assertSee('ورود برای مشاهده شماره تماس')
            ->assertDontSee('tel:09120000000', false)
            ->assertDontSee('sms:09120000000', false)
            ->assertDontSee('تماس تلفنی');
    }

    private function listing(User $seller, string $title): Listing
    {
        $brand = Brand::firstOrFail();

        return Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => $title,
            'slug' => (string) Str::uuid(),
            'price' => 32000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);
    }
}
