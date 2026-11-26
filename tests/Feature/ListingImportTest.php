<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingImportParser;
use App\Models\ListingImportTerm;
use App\Models\ModelAttribute;
use App\Models\SellerStore;
use App\Models\User;
use App\Services\ListingImport\ListingImportParserRegistry;
use Carbon\Carbon;
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

    public function test_price_rows_inherit_model_storage_and_activation_from_their_group(): void
    {
        $this->seed(CatalogSeeder::class);
        $parser = app(ListingImportParserRegistry::class)->make(ListingImportParser::where('key', 'at_kala')->firstOrFail());
        $storage = Attribute::where('slug', 'storage')->value('id');
        $rows = $parser->parse(<<<'TEXT'
iPhone 18 Pro
────────────

📱 18 Pro | 1TB | ZAA
▫️ Not Active
🍒 632,000

📱 18 Pro | 512GB | ZAA
▫️ Not Active
🩵 481,500
🍒 481,000
🖤 475,500

▫️ Active
🍒 469,000
TEXT
        )['items'];

        $this->assertSame(array_fill(0, 5, 'iPhone 18 Pro'), array_column($rows, 'model_name'));
        $this->assertSame([1024, 512, 512, 512, 512], array_map(fn ($row) => $row['attributes'][$storage], $rows));
        $this->assertSame(array_fill(0, 5, 'ZAA'), array_column($rows, 'part_number'));
        $this->assertSame(['قرمز', 'آبی', 'قرمز', 'مشکی', 'قرمز'], array_column($rows, 'color'));
        $this->assertSame(['NotACT', 'NotACT', 'NotACT', 'NotACT', 'Active'], array_column($rows, 'activation_status'));
        $this->assertSame([632000000, 481500000, 481000000, 475500000, 469000000], array_column($rows, 'price'));

        $rows = $parser->parse("📱 18 Pro | 1TB | ZAA\n▫️ Active\n🍒 632,000\n512GB\n🖤 475,500\n📱 18 Pro Max | 256GB | CH\n▫️ Not Active\n🩵 520,000")['items'];
        $this->assertSame(['iPhone 18 Pro', 'iPhone 18 Pro', 'iPhone 18 Pro Max'], array_column($rows, 'model_name'));
        $this->assertSame(['Active', 'Active', 'NotACT'], array_column($rows, 'activation_status'));
        $this->assertSame([1024, 512, 256], array_map(fn ($row) => $row['attributes'][$storage], $rows));
        $this->assertSame(['ZAA', 'ZAA', 'CH'], array_column($rows, 'part_number'));

        $rows = $parser->parse("iPhone 18 Pro 512GB ZAA\n▫️ Not Active\n🍒 481,000\n🩵 481,500\n▫️ Active\n🖤 469,000")['items'];
        $this->assertSame([512, 512, 512], array_map(fn ($row) => $row['attributes'][$storage], $rows));
        $this->assertSame(['NotACT', 'NotACT', 'Active'], array_column($rows, 'activation_status'));

        $rows = $parser->parse("iPhone 18 Pro\n512GB | ZAA\n▫️ Not Active\n🍒 481,000\n🩵 481,500")['items'];
        $this->assertSame([512, 512], array_map(fn ($row) => $row['attributes'][$storage], $rows));
        $this->assertSame(['ZAA', 'ZAA'], array_column($rows, 'part_number'));
    }

    public function test_import_page_is_not_available_without_an_assigned_parser(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('listings.import.create'))->assertForbidden();
    }

    public function test_admin_terms_prefill_catalog_options_for_complete_words_in_the_import_preview(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $activation = Attribute::query()->where('slug', 'activation_status')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.listing-imports.terms.bulk.store'), [
            'bulk_mode' => 'terms', 'bulk_lines' => "nat=فعال نشده\nact=اکتیو\nنات‌اکتیو=نات اکتیو",
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.listing-imports.terms.index'));
        $this->assertDatabaseHas('listing_import_terms', [
            'emoji' => 'nat', 'attribute_id' => $activation->id, 'attribute_value' => 'نات اکتیو',
        ]);

        $longSource = str_repeat('🟥📱', 40);
        $this->actingAs($admin)->post(route('admin.listing-imports.terms.store'), [
            'emoji' => $longSource, 'meaning' => 'نشانه ترکیبی',
        ])->assertSessionHasNoErrors();
        $this->assertSame($longSource, ListingImportTerm::query()->latest('id')->firstOrFail()->emoji);
        $this->get(route('admin.listing-imports.terms.index'))->assertOk()
            ->assertSeeText('وضعیت فعال‌سازی')->assertSeeText('نات اکتیو')->assertSeeText('لیست کلمه‌ای (فارسی و انگلیسی)');

        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $user = User::factory()->create(['listing_import_parser_id' => $parser->id]);
        $response = $this->actingAs($user)->post(route('listings.import.preview'), [
            'text' => "۱۴۰۵/۰۹/۰۵\n🟥iphone 18 promax 256GB CH NAT\n💰165/000m\n🟥iphone 18 promax 256GB CH native\n💰165/000m\n🟥iphone 18 promax 256GB CH نات اكتیو\n💰166/000m",
        ])->assertOk()->assertSee('۱۴۰۵/۰۹/۰۵');

        $rows = $response->viewData('rows');
        $this->assertCount(3, $rows);
        $this->assertTrue($rows[0]['supported']);
        $this->assertSame('iPhone 18 Pro Max', $rows[0]['model_name']);
        $this->assertSame('نات اکتیو', $rows[0]['attributes'][$activation->id]);
        $this->assertArrayNotHasKey($activation->id, $rows[1]['attributes']);
        $this->assertSame('نات اکتیو', $rows[2]['attributes'][$activation->id]);
    }

    public function test_bulk_color_terms_use_model_options_and_leave_ambiguous_colors_unselected(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $route = route('admin.listing-imports.terms.bulk.store');
        $this->actingAs($admin)->post($route, ['bulk_mode' => 'colors', 'bulk_lines' => "🟡=Yellow\n🟡=Gold\n🟡=yellow\ngld=Gold"])
            ->assertSessionHasNoErrors()->assertRedirect(route('admin.listing-imports.terms.index'));
        $term = ListingImportTerm::query()->where('emoji', '🟡')->sole();
        $this->assertSame(['Yellow', 'Gold'], $term->color_candidates);
        $this->post($route, ['bulk_mode' => 'colors', 'bulk_lines' => "🟡=Blue\ninvalid"])->assertSessionHasErrors('bulk_lines');
        $this->assertSame(['Yellow', 'Gold'], $term->fresh()->color_candidates);

        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $registry = app(ListingImportParserRegistry::class);
        $colorId = Attribute::query()->where('slug', 'color')->value('id');
        $result = $registry->make($parser)->parse("🟡13Pro 256g CH 92Bt\n💰165/000m\n🟡13Pro 256g CH 92Bt gold\n💰166/000m\n13Pro 256g CH 92Bt GLD\n💰167/000m");
        $this->assertNull($result['items'][0]['color']);
        $this->assertArrayNotHasKey($colorId, $result['items'][0]['attributes']);
        $this->assertSame('طلایی', $result['items'][1]['attributes'][$colorId]);
        $this->assertSame('طلایی', $result['items'][2]['attributes'][$colorId]);

        ModelAttribute::query()->where('phone_model_id', $result['items'][0]['phone_model_id'])
            ->where('attribute_id', $colorId)->update(['custom_options' => ['طلایی', 'آبی']]);
        $result = $registry->make($parser)->parse("🟡13Pro 256g CH 92Bt\n💰165/000m");
        $this->assertSame('طلایی', $result['items'][0]['attributes'][$colorId]);
    }

    public function test_import_uses_tehran_jalali_date_and_shows_errors_on_the_affected_row(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24 21:00:00', 'UTC'));
        $this->seed(CatalogSeeder::class);
        $parser = ListingImportParser::where('key', 'at_kala')->firstOrFail();
        $seller = User::factory()->create(['listing_import_parser_id' => $parser->id, 'can_post_listings' => true]);
        SellerStore::create(['user_id' => $seller->id, 'slug' => 'date-import-'.$seller->id, 'name' => 'فروشگاه', 'is_active' => true]);

        $this->actingAs($seller)->get(route('listings.import.create'))
            ->assertOk()->assertSee('value="۱۴۰۵/۰۹/۰۴"', false);
        $preview = $this->post(route('listings.import.preview'), [
            'text' => "📱 18 Pro | 512GB | ZAA\n▫️ Not Active\n🍒 481,000\n🍒 482,000",
            'listing_date' => '۱۴۰۵/۰۹/۰۵',
        ])->assertOk()->assertViewHas('listingDate', '2026-11-26')->assertSee('value="new" checked', false);
        $token = $preview->viewData('token');
        $resume = route('listings.import.create', ['preview' => $token]);

        $this->post(route('listings.import.store'), [
            'token' => $token,
            'listing_date' => '۱۴۰۵/۰۹/۰۵',
            'rows' => [['action' => 'skip'], ['action' => 'new']],
        ])->assertRedirect($resume)->assertSessionHasErrors('uploads.1');
        $this->get($resume)->assertOk()->assertSeeText('ردیف ۲')
            ->assertSeeText('برای این ردیف یک تصویر');
    }

    public function test_import_saves_terabyte_storage_as_the_models_catalog_option(): void
    {
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $storage = Attribute::where('slug', 'storage')->firstOrFail();
        $storage->update(['type' => 'select', 'options' => ['256', '512', '2T', '1T']]);
        $parser = ListingImportParser::where('key', 'at_kala')->firstOrFail();
        $seller = User::factory()->create(['listing_import_parser_id' => $parser->id, 'can_post_listings' => true]);
        SellerStore::create(['user_id' => $seller->id, 'slug' => 'storage-import-'.$seller->id, 'name' => 'فروشگاه', 'is_active' => true]);
        $preview = $this->actingAs($seller)->post(route('listings.import.preview'), [
            'listing_date' => '2026-11-26',
            'text' => <<<'TEXT'
📱 18 Pro | 1TB | ZAA
▫️ Not Active
🍒 632,000

📱 18 Pro | 512GB | ZAA
▫️ Not Active
🩵 481,500
🍒 481,000
🖤 475,500

▫️ Active
🍒 469,000

📱 18 Pro | 512GB | CH
▫️ Not Active
🤍 527,000

📱 18 Pro | 256GB | ZAA
▫️ Not Active
🍒 412,000
🤍 409,000
TEXT
            ,
        ])->assertOk();
        $rows = $preview->viewData('rows');
        $this->assertSame(['1T', '512', '512', '512', '512', '512', '256', '256'],
            array_map(fn (array $row) => $row['attributes'][$storage->id], $rows));

        $this->post(route('listings.import.store'), [
            'token' => $preview->viewData('token'),
            'listing_date' => '۱۴۰۵/۰۹/۰۵',
            'rows' => array_map(fn (array $row) => [
                'action' => 'new', 'price' => $row['price'], 'attributes' => $row['attributes'],
            ], $rows),
            'uploads' => array_map(fn () => UploadedFile::fake()->create('phone.webp', 20, 'image/webp'), $rows),
        ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

        $this->assertDatabaseCount('listings', 8);
        $listing = Listing::where('price', 632000000)->sole();
        $this->assertDatabaseHas('listing_attribute_values', ['listing_id' => $listing->id, 'attribute_id' => $storage->id, 'value_string' => '1T']);
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
            'listing_date' => '۱۴۰۵/۰۸/۲۶',
            'rows' => [[
                'enabled' => 1,
                'price' => $row['price'],
                'color' => 'آبی',
                'description' => 'واردشده از موجودی فروشگاه',
            ]],
            'uploads' => [UploadedFile::fake()->create('phone.webp', 100, 'image/webp')],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', ['user_id' => $user->id, 'price' => 165000000, 'created_at' => '2026-11-17 00:00:00']);
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
