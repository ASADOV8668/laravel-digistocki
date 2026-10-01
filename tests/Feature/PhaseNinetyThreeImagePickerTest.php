<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseNinetyThreeImagePickerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CatalogSeeder::class);
    }

    public function test_create_page_exposes_image_preview_picker(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee('imagePicker', false)
            ->assertSee('حداکثر ۵ تصویر')
            ->assertSee('preview.url', false);
    }

    public function test_image_picker_releases_preview_urls_and_validates_client_limits(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString('window.imagePicker =', $source);
        $this->assertStringContainsString('file.size > maxMb * 1024 * 1024', $source);
        $this->assertStringContainsString('URL.revokeObjectURL(preview.url)', $source);
    }
}
