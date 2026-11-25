<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingImportParser;
use App\Models\SellerStore;
use App\Models\User;
use App\Services\ListingImport\ListingImportParserRegistry;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_at_kala_parser_extracts_rows_and_removes_exact_duplicates(): void
    {
        $this->seed(CatalogSeeder::class);
        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $text = "💙13Pro 256g CH 92Bt\n💰165/000m\n💙13Pro 256g CH 92Bt\n💰165/000m\n🖤13Pro 256g CH 95Bt\n💰165/000m";

        $result = app(ListingImportParserRegistry::class)->make($parser)->parse($text);

        $this->assertCount(2, $result['items']);
        $this->assertSame('آبی', $result['items'][0]['color']);
        $this->assertSame(165000000, $result['items'][0]['price']);
        $this->assertSame(92, $result['items'][0]['attributes'][Attribute::query()->where('slug', 'battery_health')->value('id')]);
        $this->assertTrue($result['date_required']);
    }

    public function test_admin_can_assign_an_import_parser_to_a_user_and_parser_menu_shows_sample(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $parser = ListingImportParser::query()->where('key', 'bamatel')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'mobile' => '0912'.str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
            'email' => $user->email,
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 1,
            'listing_import_parser_id' => $parser->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'listing_import_parser_id' => $parser->id]);
        $this->actingAs($admin)->get(route('admin.listing-imports.index', ['parser' => 'bamatel']))
            ->assertOk()
            ->assertSeeText('باماتل')
            ->assertSeeText('لیست قیمت کارکرده‌ باماتل');
    }

    public function test_fratel_and_bamatel_parsers_keep_price_and_do_not_confuse_battery_or_cycle(): void
    {
        $this->seed(CatalogSeeder::class);
        $fratel = ListingImportParser::query()->where('key', 'fratel')->firstOrFail();
        $bamatel = ListingImportParser::query()->where('key', 'bamatel')->firstOrFail();

        $fratelResult = app(ListingImportParserRegistry::class)->make($fratel)->parse("IPHONE STOCK\n‼️13 Pro 256 ZAA‼️\n🔋100% 🔵465.000(✅)\n🔋93% 🤍475.000(✅)");
        $bamatelResult = app(ListingImportParserRegistry::class)->make($bamatel)->parse("📱17 pro max 256G\nzaa 100% Cycle : 62\n💵 499,900\nرجیستر ✅");

        $this->assertSame(465000000, $fratelResult['items'][0]['price']);
        $this->assertSame('آبی', $fratelResult['items'][0]['color']);
        $this->assertSame(499900000, $bamatelResult['items'][0]['price']);
        $this->assertSame(62, $bamatelResult['items'][0]['cycle']);
    }

    public function test_admin_can_create_and_edit_the_price_list_pattern(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $sample = "iPhone 13 Pro 256GB | آبی\nقیمت: ۱۶۵٬۰۰۰٬۰۰۰ تومان";
        $data = [
            'key' => 'custom-shop',
            'title' => 'قالب فروشگاه',
            'pattern' => "{model} | {color}\nقیمت: {price} تومان",
            'sample_text' => $sample,
            'is_active' => 1,
        ];

        $this->actingAs($admin)->get(route('admin.listing-imports.create'))->assertOk();
        $this->post(route('admin.listing-imports.store'), $data)
            ->assertRedirect(route('admin.listing-imports.index', ['parser' => 'custom-shop']));
        $parser = ListingImportParser::query()->where('key', 'custom-shop')->firstOrFail();
        $this->get(route('admin.listing-imports.edit', $parser))->assertOk();

        $registry = app(ListingImportParserRegistry::class);
        $result = $registry->make($parser)->parse($sample);
        $this->assertCount(1, $result['items']);
        $this->assertTrue($result['items'][0]['supported']);
        $this->assertSame(165000000, $result['items'][0]['price']);

        $this->put(route('admin.listing-imports.update', $parser), array_replace($data, ['pattern' => '{model}']))
            ->assertSessionHasErrors('pattern');

        $updatedSample = 'iPhone 13 Pro 256GB - 170,000,000 تومان';
        $this->put(route('admin.listing-imports.update', $parser), array_replace($data, [
            'pattern' => '{model} - {price} تومان',
            'sample_text' => $updatedSample,
            'is_active' => 0,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $parser->refresh();
        $this->assertFalse($parser->is_active);
        $result = $registry->make($parser)->parse($updatedSample);
        $this->assertSame(170000000, $result['items'][0]['price']);
    }

    public function test_import_page_is_not_available_without_an_assigned_parser(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('listings.import.create'))->assertForbidden();
    }

    public function test_supported_preview_rows_can_be_created_with_an_uploaded_image(): void
    {
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create(['can_post_listings' => true]);
        SellerStore::create(['user_id' => $user->id, 'slug' => 'import-store-'.$user->id, 'name' => 'فروشگاه ایمپورت', 'is_active' => true]);
        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $user->forceFill(['listing_import_parser_id' => $parser->id])->save();
        $text = "💙13Pro 256g CH 92Bt\n💰165/000m";

        $this->actingAs($user)->post(route('listings.import.preview'), [
            'text' => $text,
            'listing_date' => '2026-11-17',
        ])->assertOk()
            ->assertSee('attribute_definitions')
            ->assertSee('toggleAttribute')
            ->assertDontSee('<select', false)
            ->assertSee('dir="ltr"', false);

        $parserRows = app(ListingImportParserRegistry::class)->make($parser)->parse($text, '2026-11-17');
        $token = 'test-import-token';
        $row = $parserRows['items'][0];
        $this->withSession(['listing_import.preview.'.$token => ['parser_id' => $parser->id, 'rows' => $parserRows['items'], 'date' => '2026-11-17']]);
        $this->actingAs($user)->post(route('listings.import.store'), [
            'token' => $token,
            'listing_date' => '2026-11-17',
            'rows' => [[
                'enabled' => 1,
                'price' => $row['price'],
                'color' => 'آبی',
                'description' => 'واردشده از موجودی فروشگاه',
            ]],
            'uploads' => [UploadedFile::fake()->create('phone.webp', 100, 'image/webp')],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', ['user_id' => $user->id, 'price' => 165000000]);
        $this->assertDatabaseCount('listing_images', 1);
    }

    public function test_preview_prefills_latest_primary_media_for_the_same_user_and_model(): void
    {
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create(['can_post_listings' => true]);
        SellerStore::create(['user_id' => $user->id, 'slug' => 'import-store-media-'.$user->id, 'name' => 'فروشگاه رسانه', 'is_active' => true]);
        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $user->forceFill(['listing_import_parser_id' => $parser->id])->save();
        $parsed = app(ListingImportParserRegistry::class)->make($parser)->parse("💙13Pro 256g CH 92Bt\n💰165/000m");
        $source = $parsed['items'][0];
        $media = $user->addMedia(UploadedFile::fake()->create('previous-primary.jpg', 100, 'image/jpeg'))->toMediaCollection('listing-images', 'public');
        $listing = Listing::create([
            'user_id' => $user->id,
            'brand_id' => $source['brand_id'],
            'phone_model_id' => $source['phone_model_id'],
            'title' => 'آگهی قبلی',
            'slug' => 'previous-primary-'.$user->id,
            'price' => 165000000,
            'status' => 'approved',
        ]);
        ListingImage::create(['listing_id' => $listing->id, 'media_id' => $media->id, 'path' => 'media/previous-primary.jpg', 'thumbnail_path' => 'media/previous-primary.jpg', 'is_primary' => true, 'sort_order' => 0]);

        $response = $this->actingAs($user)->post(route('listings.import.preview'), [
            'text' => "💙13Pro 256g CH 92Bt\n💰165/000m",
            'listing_date' => '2026-11-18',
        ]);
        $response->assertOk()
            ->assertSee('media_id\\u0022:'.$media->id, false)
            ->assertSee('previous-primary.jpg', false);
    }
}
