<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UploadSizeValidationTest extends TestCase
{
    public function test_file_validation_messages_show_megabytes_instead_of_kilobytes(): void
    {
        $validator = Validator::make(
            ['image' => UploadedFile::fake()->create('large.jpg', 6000, 'image/jpeg')],
            ['image' => ['file', 'max:5120']],
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('۵ مگابایت', $validator->errors()->first('image'));
        $this->assertStringNotContainsString('کیلوبایت', $validator->errors()->first('image'));
    }

    public function test_frontend_upload_guard_is_loaded_for_public_and_admin_layouts(): void
    {
        $script = file_get_contents(resource_path('js/file-upload-validation.js'));

        $this->assertStringContainsString('DataTransfer', $script);
        $this->assertStringContainsString('show-notification', $script);
        $this->assertStringContainsString('admin-notification', $script);
        $this->assertStringContainsString('max-image-upload-mb', file_get_contents(resource_path('views/layouts/app.blade.php')));
        $this->assertStringContainsString('max-image-upload-mb', file_get_contents(resource_path('views/layouts/admin.blade.php')));
    }
}
