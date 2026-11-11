<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandFeaturedImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_a_featured_brand_image_and_home_uses_the_default_image(): void
    {
        $this->seed(CatalogSeeder::class);
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);
        $brand = Brand::query()->where('name_en', 'Apple')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.brands.index'))
            ->assertOk()
            ->assertSee('featured_image', false)
            ->assertSee('images/brands/samsung.webp', false);

        $this->actingAs($admin)
            ->patch(route('admin.brands.update', $brand), [
                'name' => $brand->name,
                'name_en' => $brand->name_en,
                'featured_image' => UploadedFile::fake()->create('apple-featured.png', 100, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $brand->refresh();
        $this->assertNotNull($brand->featured_image);
        Storage::disk('public')->assertExists($brand->featured_image);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($brand->featuredImageUrl(), false);

        $otherBrand = Brand::query()->where('name_en', 'Samsung')->firstOrFail();
        $this->assertSame(asset('images/brands/samsung.webp'), $otherBrand->featuredImageUrl());

        $this->assertSame(
            asset('images/brands/huawei.webp'),
            Brand::query()->where('name_en', 'Huawei')->firstOrFail()->featuredImageUrl(),
        );

        $unknownBrand = Brand::create([
            'name' => 'برند تست',
            'name_en' => 'Test Brand',
            'slug' => 'test-brand',
            'is_active' => true,
        ]);
        $this->assertSame(asset('images/brand-default.png'), $unknownBrand->featuredImageUrl());
    }
}
