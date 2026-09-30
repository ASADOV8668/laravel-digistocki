<?php

namespace Tests\Feature;

use App\Services\SystemOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseNinetyNineSystemOptionsLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_options_are_shared_per_request_and_loaded_once(): void
    {
        DB::enableQueryLog();

        $first = app(SystemOptions::class);
        $first->get('site_title');
        $second = app(SystemOptions::class);
        $second->get('page_title_prefix');

        $optionReads = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'select')
                && str_contains(strtolower($query['query']), 'from `options`'))
            ->values();

        $this->assertSame($first, $second);
        $this->assertCount(1, $optionReads);
    }
}
