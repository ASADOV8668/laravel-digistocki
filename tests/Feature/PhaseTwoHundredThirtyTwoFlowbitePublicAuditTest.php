<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PhaseTwoHundredThirtyTwoFlowbitePublicAuditTest extends TestCase
{
    public function test_public_views_have_no_known_legacy_flowbite_patterns(): void
    {
        $views = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getExtension() === 'php' && str_ends_with($file->getFilename(), '.blade.php'))
            ->map(fn ($file) => $file->getPathname())
            ->reject(fn (string $path) => str_contains(str_replace('\\', '/', $path), '/admin/'));

        $source = $views->map(fn (string $path) => file_get_contents($path))->implode("\n");

        $this->assertStringNotContainsString('rounded-[2rem]', $source);
        $this->assertStringNotContainsString('border-0 bg-slate-50', $source);
        $this->assertStringContainsString('data-drawer-target', $source);
        $this->assertStringContainsString('data-modal-toggle', $source);
        $this->assertStringContainsString('data-tooltip-target', $source);
        $this->assertStringContainsString('data-carousel="slide"', $source);
        $this->assertStringContainsString('data-accordion="collapse"', $source);
        $this->assertStringContainsString('vendor.pagination.flowbite', $source);
    }
}
