<?php

namespace Tests\Feature;

use App\Models\Attribute;
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
}
