<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BaleAdminBotTest extends TestCase
{
    use RefreshDatabase;

    private string $secret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = str_repeat('a', 64);
        config(['services.bale.token' => 'test-token', 'services.bale.webhook_secret' => $this->secret]);
        Http::preventStrayRequests();
        Http::fake(['https://tapi.bale.ai/*' => Http::response(['ok' => true])]);
    }

    public function test_pairing_requires_an_admin_and_access_ends_when_the_role_is_removed(): void
    {
        $this->update('/start', 1, 'group')->assertNoContent();
        $this->update('/start', 1, 'private', str_repeat('b', 64))->assertNotFound();
        Http::assertNothingSent();

        $this->update('/start', 1)->assertNoContent();
        preg_match('/\b[a-f0-9]{32}\b/', $this->lastReply(), $matches);
        $code = $matches[0];

        $this->actingAs(User::factory()->create())->post(route('admin.bale.store'), ['code' => $code])->assertForbidden();
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->post(route('admin.bale.store'), ['code' => $code])->assertRedirect();
        $this->assertDatabaseHas('bale_admin_connections', ['user_id' => $admin->id, 'code_hash' => null]);

        $this->update('/report', 2)->assertNoContent();
        $this->assertStringContainsString('آگهی‌های جدید امروز', $this->lastReply());
        $this->artisan('bale:daily-report')->assertSuccessful();
        $this->assertStringContainsString('آگهی‌های جدید امروز', $this->lastReply());

        $admin->forceFill(['role' => 'user'])->save();
        $this->update('/report', 3)->assertNoContent();
        $this->assertStringNotContainsString('آگهی‌های جدید امروز', $this->lastReply());
        Http::assertSentCount(4);
    }

    public function test_listing_review_uses_the_admin_account_and_replayed_updates_cannot_change_it_again(): void
    {
        $this->seed(CatalogSeeder::class);
        $brand = Brand::firstOrFail();
        $owner = User::factory()->create();
        $listing = Listing::create([
            'user_id' => $owner->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی آزمایشی',
            'slug' => Str::uuid(),
            'status' => ListingStatus::Pending,
        ]);
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->update('/start', 1)->assertNoContent();
        preg_match('/\b[a-f0-9]{32}\b/', $this->lastReply(), $matches);
        $this->actingAs($admin)->post(route('admin.bale.store'), ['code' => $matches[0]])->assertRedirect();

        $this->update('/pending', 2)->assertNoContent();
        $this->assertStringContainsString($listing->title, $this->lastReply());

        $this->update("/reject {$listing->id} اطلاعات آگهی ناقص است", 3)->assertNoContent();
        $this->assertSame(ListingStatus::Rejected, $listing->refresh()->status);
        $this->assertSame('اطلاعات آگهی ناقص است', $listing->rejection_reason);
        $this->assertDatabaseHas('bale_admin_actions', ['admin_id' => $admin->id, 'listing_id' => $listing->id, 'action' => 'reject']);

        $listing->update(['status' => ListingStatus::Pending]);
        $this->update("/approve {$listing->id}", 3)->assertNoContent();
        $this->assertSame(ListingStatus::Pending, $listing->refresh()->status);
        $this->update("/approve {$listing->id}", 4)->assertNoContent();
        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
        $this->assertDatabaseCount('bale_admin_actions', 2);
        $this->assertSame(2, $owner->notifications()->count());
    }

    public function test_webhook_registration_uses_the_configured_https_site(): void
    {
        config(['app.url' => 'http://127.0.0.1:8015', 'services.bale.webhook_origin' => 'https://stage.example.test']);

        $this->artisan('bale:register-webhook')->assertSuccessful();

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/setWebhook'));
        $this->assertSame("https://stage.example.test/integrations/bale/{$this->secret}", Http::recorded()->sole()[0]['url']);
    }

    public function test_expired_codes_are_rejected_and_disconnect_stops_reports(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['role' => 'admin'])->save();

        $this->update('/start', 1)->assertNoContent();
        preg_match('/\b[a-f0-9]{32}\b/', $this->lastReply(), $matches);
        $this->travel(11)->minutes();

        $this->actingAs($admin)->post(route('admin.bale.store'), ['code' => $matches[0]])
            ->assertSessionHasErrors('code');

        $this->update('/start', 2)->assertNoContent();
        preg_match('/\b[a-f0-9]{32}\b/', $this->lastReply(), $matches);
        $this->post(route('admin.bale.store'), ['code' => $matches[0]])->assertSessionHasNoErrors();
        $this->delete(route('admin.bale.destroy'))->assertRedirect();

        $this->update('/report', 3)->assertNoContent();
        $this->assertStringNotContainsString('آگهی‌های جدید امروز', $this->lastReply());
        $this->artisan('bale:daily-report')->assertSuccessful();
        Http::assertSentCount(3);
    }

    private function update(string $text, int $updateId, string $type = 'private', ?string $secret = null)
    {
        return $this->postJson(route('bale.webhook', ['secret' => $secret ?? $this->secret]), [
            'update_id' => $updateId,
            'message' => ['from' => ['id' => 7], 'chat' => ['id' => 8, 'type' => $type], 'text' => $text],
        ]);
    }

    private function lastReply(): string
    {
        return Http::recorded()->last()[0]['text'];
    }
}
