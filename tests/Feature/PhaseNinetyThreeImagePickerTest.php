<?php

namespace Tests\Feature;

use App\Models\SellerStore;
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
        $user = User::factory()->create();
        SellerStore::create(['user_id' => $user->id, 'slug' => 'picker-store-'.$user->id, 'name' => 'فروشگاه تصویر']);
        $this->actingAs($user)
            ->get(route('listings.create'))
            ->assertOk()
            ->assertSee('imagePicker', false)
            ->assertSee('حداکثر ۵ تصویر')
            ->assertSee('حداقل یک تصویر', false)
            ->assertSee('preview.url', false);
    }

    public function test_image_picker_releases_preview_urls_and_validates_client_limits(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($source);
        $this->assertStringContainsString('window.imagePicker =', $source);
        $this->assertStringContainsString('file.size > maxMb * 1024 * 1024', $source);
        $this->assertStringContainsString('new DataTransfer()', $source);
        $this->assertStringContainsString('event.target.files = transfer.files', $source);
        $this->assertStringContainsString('URL.revokeObjectURL(preview.url)', $source);
    }

    public function test_public_and_admin_forms_offer_click_to_choose_primary_image(): void
    {
        $publicCreate = file_get_contents(resource_path('views/listings/create.blade.php'));
        $publicEdit = file_get_contents(resource_path('views/listings/edit.blade.php'));
        $adminForm = file_get_contents(resource_path('views/admin/listings/form.blade.php'));

        $this->assertStringContainsString('setPrimary(previews.indexOf(preview))', $publicCreate);
        $this->assertStringContainsString('setPrimary(\'\', \'{{ $image->id }}\')', $publicEdit);
        $this->assertStringContainsString('setPrimary(\'\', \'{{ $image->id }}\')', $adminForm);
        $this->assertStringContainsString('name="primary_image_index"', $publicCreate);
        $this->assertStringContainsString('name="primary_image_id"', $publicEdit);
        $this->assertStringContainsString('bg-success', $adminForm);
    }
}
