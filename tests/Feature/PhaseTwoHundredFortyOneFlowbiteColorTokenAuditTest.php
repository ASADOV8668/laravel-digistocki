<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredFortyOneFlowbiteColorTokenAuditTest extends TestCase
{
    public function test_public_views_do_not_use_undefined_primary_focus_tokens(): void
    {
        $views = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getExtension() === 'php')
            ->reject(fn ($file) => str_contains(str_replace('\\', '/', $file->getPathname()), '/admin/'));

        $source = $views->map(fn ($file) => File::get($file->getPathname()))->implode("\n");

        $this->assertStringNotContainsString('focus:ring-primary-200', $source);
        $this->assertStringContainsString('focus:ring-primary/30', $source);
    }
}
