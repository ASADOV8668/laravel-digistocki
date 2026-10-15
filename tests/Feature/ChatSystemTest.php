<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Brand;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_user_can_start_a_conversation_send_a_message_and_view_it(): void
    {
        Storage::fake('public');
        [$seller, $buyer, $listing] = $this->listingPair();
        ListingImage::create([
            'listing_id' => $listing->id,
            'path' => 'listings/chat-test/phone.webp',
            'thumbnail_path' => 'listings/chat-test/thumb.webp',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $response = $this->actingAs($buyer)->post(route('chats.from-listing', $listing));
        $conversation = ChatConversation::firstOrFail();

        $response->assertRedirect(route('chats.show', $conversation));

        $this->actingAs($buyer)
            ->postJson(route('chats.messages.store', $conversation), [
                'body' => 'سلام، این آگهی هنوز موجود است؟',
            ])
            ->assertOk()
            ->assertJsonPath('last_message_id', ChatMessage::firstOrFail()->id);

        $this->actingAs($seller)
            ->get(route('chats.index'))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertSee('storage/listings/chat-test/thumb.webp')
            ->assertSee('سلام، این آگهی هنوز موجود است؟');

        $this->assertSame(1, ChatMessage::unreadCountForUser($seller->id));

        $this->actingAs($seller)
            ->get(route('chats.show', $conversation))
            ->assertOk()
            ->assertSee('سلام، این آگهی هنوز موجود است؟')
            ->assertSee('storage/listings/chat-test/thumb.webp')
            ->assertSee('data-chat-room')
            ->assertSee('setInterval(refresh, 1000)')
            ->assertDontSee('حذف چت');

        $this->assertSame(0, ChatMessage::unreadCountForUser($seller->id));
    }

    public function test_chat_image_is_stored_and_archive_command_removes_conversation_and_image(): void
    {
        Storage::fake('public');
        [$seller, $buyer, $listing] = $this->listingPair();
        $this->actingAs($buyer)->post(route('chats.from-listing', $listing));
        $conversation = ChatConversation::firstOrFail();

        $this->actingAs($buyer)->postJson(route('chats.messages.store', $conversation), [
                'image' => UploadedFile::fake()->create('phone.jpg', 100, 'image/jpeg'),
        ])->assertOk();

        $message = ChatMessage::firstOrFail();
        Storage::disk('public')->assertExists($message->image_path);

        $conversation->forceFill(['created_at' => now()->subDays(121)])->save();
        $this->artisan('chats:prune', ['--days' => 120])->assertExitCode(0);

        $this->assertDatabaseMissing('chat_conversations', ['id' => $conversation->id]);
        Storage::disk('public')->assertMissing($message->image_path);
    }

    public function test_users_cannot_start_a_chat_with_their_own_listing(): void
    {
        [$seller, , $listing] = $this->listingPair();

        $this->actingAs($seller)
            ->post(route('chats.from-listing', $listing))
            ->assertStatus(422);
    }

    private function listingPair(): array
    {
        $seller = User::factory()->create(['mobile' => '09120000000']);
        $buyer = User::factory()->create(['mobile' => '09121111111']);
        $brand = Brand::firstOrFail();
        $listing = Listing::create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'phone_model_id' => $brand->phoneModels()->firstOrFail()->id,
            'title' => 'آگهی تست چت',
            'slug' => (string) Str::uuid(),
            'price' => 32000000,
            'status' => ListingStatus::Approved,
            'published_at' => now(),
        ]);

        return [$seller, $buyer, $listing];
    }
}
