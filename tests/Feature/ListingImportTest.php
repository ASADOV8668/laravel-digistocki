<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\ListingImportParser;
use App\Models\PhoneModel;
use App\Models\SellerStore;
use App\Models\User;
use App\Services\ListingImport\ListingImportParserRegistry;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
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

    public function test_admin_manages_import_access_through_storefront_settings(): void
    {
        $this->seed(CatalogSeeder::class);
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create();
        $parser = ListingImportParser::query()->where('key', 'bamatel')->firstOrFail();

        $store = $this->createImportStore($user);
        $this->actingAs($admin)->get(route('admin.users.edit', $user))
            ->assertOk()->assertDontSee('name="listing_import_parser_id"', false);
        $this->get(route('admin.storefronts.edit', $user))
            ->assertOk()->assertSee('name="listing_import_parser_id"', false);
        $this->put(route('admin.storefronts.update', $user), [
            'name' => $store->name,
            'slug' => $store->slug,
            'listing_import_parser_id' => $parser->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->put(route('admin.users.update', $user), [
            'name' => $user->name,
            'mobile' => '0912'.str_pad((string) $user->id, 7, '0', STR_PAD_LEFT),
            'email' => $user->email,
            'role' => 'user',
            'is_active' => 1,
            'can_post_listings' => 0,
            'listing_import_parser_id' => null,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'listing_import_parser_id' => $parser->id, 'can_post_listings' => true]);
        $this->actingAs($admin)->get(route('admin.listing-imports.index', ['parser' => 'bamatel']))
            ->assertOk()
            ->assertSeeText('باماتل')
            ->assertSeeText('لیست قیمت کارکرده‌ باماتل');

        $this->put(route('admin.storefronts.update', $user), [
            'name' => $store->name,
            'slug' => $store->slug,
            'listing_import_parser_id' => null,
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'listing_import_parser_id' => null]);
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
        $this->assertTrue($bamatelResult['items'][0]['registered']);
        $batteryId = Attribute::query()->where('slug', 'battery_health')->value('id');
        $this->assertSame(100, $bamatelResult['items'][0]['attributes'][$batteryId]);
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

        $updatedSample = "iPhone 13 Pro | 🟢 💾 🟦 🔋 | 170,000,000 تومان\niPhone 13 Pro | ❌ ۵۱۲ گیگ 🔴 91% | 175,000,000 تومان\niPhone 13 Pro | ✅ ❌ 256GB | 180,000,000 تومان";
        $this->put(route('admin.listing-imports.update', $parser), array_replace($data, [
            'pattern' => '{model} | {markers} | {price} تومان',
            'sample_text' => $updatedSample,
            'symbols' => [
                'registration' => '🟢=۱',
                'storage' => '💾=۲۵۶',
                'color' => '🟦=آبی',
                'battery' => '🔋=95',
            ],
            'is_active' => 1,
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $parser->refresh();
        $user = User::factory()->create(['can_post_listings' => true, 'listing_import_parser_id' => $parser->id]);
        $this->createImportStore($user);
        $response = $this->actingAs($user)->post(route('listings.import.preview'), ['text' => $updatedSample])
            ->assertOk()
            ->assertSee('وضعیت رجیستری');
        $rows = $response->viewData('rows');
        $this->assertSame(170000000, $rows[0]['price']);
        $this->assertTrue($rows[0]['registered']);
        $this->assertContains('registered', array_column($rows[0]['attribute_definitions'], 'slug'));
        $this->assertSame('آبی', $rows[0]['color']);
        $attributes = Attribute::query()->pluck('id', 'slug');
        $this->assertSame(256, $rows[0]['attributes'][$attributes['storage']]);
        $this->assertSame(95, $rows[0]['attributes'][$attributes['battery_health']]);
        $this->assertFalse($rows[1]['registered']);
        $this->assertSame(512, $rows[1]['attributes'][$attributes['storage']]);
        $this->assertNull($rows[2]['registered']);
    }

    public function test_import_page_is_not_available_without_an_assigned_parser(): void
    {
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('listings.import.create'))->assertForbidden();
    }

    public function test_unified_import_recognizes_the_three_formats_in_a_single_price_list(): void
    {
        $this->seed(CatalogSeeder::class);
        $parser = ListingImportParser::query()->where('key', 'unified_price_list')->sole();
        $user = User::factory()->create(['can_post_listings' => true, 'listing_import_parser_id' => $parser->id]);
        $this->createImportStore($user);
        $text = "💙13Pro 256g CH 92BT\n💰172/000m\n"
            ."‼️17 Pro Max 512 ZAA‼️\n🔋100% 🔵465.000(✅)\n93🔋 🤍475.000(🚫)\n"
            ."📱15 Pro Max 512G 🟦\nZAA 89bt cycle: 62\n💵244,900\nرجیستر ✅\n"
            ."🔷 iPhone 14 Pro 256 GB 91🔋 ✅ 💰170,000,000 تومان\n"
            .'iPhone 14 Pro 256 GB آبی اقیانوسی 94BT 💰180,000,000 تومان';

        $response = $this->actingAs($user)->post(route('listings.import.preview'), ['text' => $text])->assertOk();
        $rows = $response->viewData('rows');
        $attributes = Attribute::query()->pluck('id', 'slug');

        $this->assertCount(6, $rows);
        $this->assertSame([172000000, 465000000, 475000000, 244900000, 170000000, 180000000], array_column($rows, 'price'));
        $this->assertSame([null, true, false, true, true, null], array_column($rows, 'registered'));
        $this->assertSame(['آبی', 'آبی', 'سفید', 'آبی', 'آبی', 'آبی'], array_column($rows, 'color'));
        $this->assertSame([92, 100, 93, 89, 91, 94], array_map(fn (array $row) => $row['attributes'][$attributes['battery_health']], $rows));
        $this->assertSame([256, 512, 512, 512, 256, 256], array_map(fn (array $row) => $row['attributes'][$attributes['storage']], $rows));

        $model = PhoneModel::query()->findOrFail($rows[5]['phone_model_id']);
        $model->modelAttributes()->where('attribute_id', $attributes['color'])->firstOrFail()
            ->update(['custom_options' => ['آبی آسمانی', 'آبی اقیانوسی', 'سفید']]);
        $response = $this->post(route('listings.import.preview'), ['text' => $text])->assertOk();
        $this->assertSame('آبی اقیانوسی', $response->viewData('rows')[5]['attributes'][$attributes['color']]);
    }

    public function test_user_without_an_approved_store_cannot_submit_or_import_listings(): void
    {
        $parser = ListingImportParser::query()->where('key', 'at_kala')->sole();
        $user = User::factory()->create(['listing_import_parser_id' => $parser->id]);

        $this->actingAs($user)->get(route('listings.import.create'))->assertForbidden();
        $this->get(route('listings.create'))->assertRedirect(route('storefront.edit'));
        $this->post(route('listings.store'))->assertRedirect(route('home'));

        $store = $this->createImportStore($user);
        $store->update(['approval_status' => 'pending']);
        $this->actingAs($user->fresh())->post(route('listings.import.preview'))->assertForbidden();
        $this->post(route('listings.import.store'))->assertForbidden();
        $this->post(route('listings.store'))->assertRedirect(route('home'));
        $this->assertDatabaseCount('listings', 0);
    }

    private function createImportStore(User $user): SellerStore
    {
        return $user->storefront()->create([
            'slug' => 'import-store-'.$user->id,
            'name' => 'فروشگاه ایمپورت',
            'approval_status' => 'approved',
            'logo_path' => 'stores/logo.webp',
        ]);
    }

    public function test_supported_preview_rows_can_be_created_with_an_uploaded_image(): void
    {
        $this->travelTo(Carbon::parse('2026-11-24 21:00:00', 'UTC'));
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create(['can_post_listings' => true]);
        $this->createImportStore($user);
        $parser = ListingImportParser::query()->where('key', 'at_kala')->firstOrFail();
        $user->forceFill(['listing_import_parser_id' => $parser->id])->save();
        $text = "1405/08/01\n💙13Pro 256g CH 92Bt\n💰165/000m";

        $this->actingAs($user)->get(route('listings.import.create'))
            ->assertOk()->assertViewHas('listingDate', '2026-11-25');
        $this->post(route('listings.import.preview'), ['text' => $text])
            ->assertOk()->assertViewHas('listingDate', '2026-11-25');

        $preview = $this->post(route('listings.import.preview'), [
            'text' => $text,
            'listing_date' => '2026-11-17',
        ])->assertOk()
            ->assertViewHas('listingDate', '2026-11-17')
            ->assertSee('attribute_definitions')
            ->assertSee('toggleAttribute')
            ->assertDontSee('<select', false)
            ->assertSee('dir="ltr"', false);

        $token = $preview->viewData('token');
        $row = $preview->viewData('rows')[0];
        $attributes = Attribute::query()->pluck('id', 'slug');
        $this->actingAs($user)->post(route('listings.import.store'), [
            'token' => $token,
            'listing_date' => '2026-11-18',
            'rows' => [[
                'enabled' => 1,
                'price' => $row['price'],
                'color' => 'آبی',
                'description' => 'واردشده از موجودی فروشگاه',
                'attributes' => [
                    $attributes['battery_health'] => '۹۱',
                    $attributes['registered'] => '0',
                ],
            ]],
            'uploads' => [UploadedFile::fake()->create('phone.webp', 100, 'image/webp')],
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('listings', ['user_id' => $user->id, 'price' => 165000000]);
        $this->assertDatabaseCount('listing_images', 1);
        $listing = Listing::query()->where('user_id', $user->id)->sole();
        $this->assertSame('2026-11-18', $listing->created_at->toDateString());
        $this->assertDatabaseHas('listing_attribute_values', ['listing_id' => $listing->id, 'attribute_id' => $attributes['battery_health'], 'value_integer' => 91]);
        $this->assertDatabaseHas('listing_attribute_values', ['listing_id' => $listing->id, 'attribute_id' => $attributes['registered'], 'value_boolean' => false]);
        $this->assertDatabaseHas('listing_attribute_values', ['listing_id' => $listing->id, 'attribute_id' => $attributes['color'], 'value_string' => 'آبی']);
    }

    public function test_preview_prefills_latest_primary_media_for_the_same_user_and_model(): void
    {
        Storage::fake('public');
        $this->seed(CatalogSeeder::class);
        $user = User::factory()->create(['can_post_listings' => true]);
        $this->createImportStore($user);
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
