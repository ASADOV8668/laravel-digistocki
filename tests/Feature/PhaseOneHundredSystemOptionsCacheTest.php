<?php

namespace Tests\Feature;

use App\Services\SystemOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseOneHundredSystemOptionsCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_options_snapshot_is_reused_by_new_service_instances_and_invalidated_on_write(): void
    {
        app(SystemOptions::class)->get('site_title');
        app()->forgetInstance(SystemOptions::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $cached = app(SystemOptions::class);
        $this->assertNotNull($cached->get('page_title_prefix'));

        $optionReads = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'from `options`'))
            ->values();
        $this->assertCount(0, $optionReads);

        $cached->set('site_title', 'عنوان تازه');
        app()->forgetInstance(SystemOptions::class);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $fresh = app(SystemOptions::class);
        $this->assertSame('عنوان تازه', $fresh->get('site_title'));

        $optionReadsAfterWrite = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'from `options`'))
            ->values();
        $this->assertCount(1, $optionReadsAfterWrite);
    }
}
