<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\PhoneModel;
use App\Models\User;
use App\Notifications\ListingStatusNotification;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BuildsListingAttributePayload;
use Tests\TestCase;

class NotificationListingCycleTest extends TestCase
{
    use BuildsListingAttributePayload;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_rejected_listing_can_be_revised_and_approved_again(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $reason = 'تصویر آگهی واضح نیست.';
        $listing = $this->listing($owner, $brand, $model, ListingStatus::Rejected, $reason);

        $owner->notify(new ListingStatusNotification($listing, 'rejected', $reason));

        $this->assertSame(['database'], (new ListingStatusNotification($listing, 'rejected', $reason))->via($owner));
        $this->actingAs($owner)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($reason)
            ->assertSee('آگهی نیاز به اصلاح دارد')
            ->assertSee('ویرایش و ارسال مجدد');
        $this->actingAs($owner)
            ->get(route('listings.edit', $listing))
            ->assertOk()
            ->assertSee('علت رد آگهی')
            ->assertSee($reason);

        $response = $this->actingAs($owner)->put(route('listings.update', $listing), [
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی اصلاح‌شده',
            'description' => 'توضیحات اصلاح‌شده',
            'price' => 32000000,
            'attributes' => $this->requiredAttributeValues($model),
        ]);

        $response->assertRedirect();
        $listing->refresh();
        $this->assertSame(ListingStatus::Pending, $listing->status);
        $this->assertNull($listing->rejection_reason);

        $this->actingAs($admin)->patch(route('admin.listings.approve', $listing))->assertRedirect();
        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
        $statuses = $owner->notifications()->get()->pluck('data.status')->all();
        $this->assertContains('rejected', $statuses);
        $this->assertContains('approved', $statuses);
    }

    public function test_admin_rejection_form_uses_textarea_for_reason(): void
    {
        [$owner, $brand, $model] = $this->context();
        $admin = User::factory()->create()->forceFill(['role' => 'admin']);
        $admin->save();
        $this->listing($owner, $brand, $model, ListingStatus::Pending);

        $this->actingAs($admin)
            ->get(route('admin.listings.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('<textarea', false)
            ->assertSee('name="rejection_reason"', false)
            ->assertDontSee('<input name="rejection_reason"', false);
    }

    private function context(): array
    {
        $brand = Brand::firstOrFail();

        return [User::factory()->create(), $brand, $brand->phoneModels()->firstOrFail()];
    }

    private function listing(User $user, Brand $brand, PhoneModel $model, ListingStatus $status, ?string $reason = null): Listing
    {
        return Listing::create([
            'user_id' => $user->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $model->id,
            'title' => 'آگهی ردشده '.Str::random(5),
            'slug' => Str::uuid()->toString(),
            'price' => 1000000,
            'status' => $status,
            'rejection_reason' => $reason,
        ]);
    }
}
