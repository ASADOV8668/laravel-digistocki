<?php

namespace Tests\Feature;

use App\Models\SystemOption;
use App\Services\SystemOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PhaseNinetyEightSystemOptionsBatchPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_options_are_saved_in_one_batch_and_cached_values_are_invalidated(): void
    {
        $options = app(SystemOptions::class);
        $options->get('site_title');

        DB::enableQueryLog();

        $options->setMany([
            'site_title' => ['بازار تست', 'string'],
            'page_title_prefix' => ['بازار', 'string'],
            'max_image_upload_mb' => [12, 'integer'],
        ]);

        $table = (new SystemOption)->getTable();
        $writes = collect(DB::getQueryLog())
            ->filter(fn (array $query) => str_contains(strtolower($query['query']), 'insert into')
                && str_contains(strtolower($query['query']), strtolower($table)))
            ->values();

        $this->assertCount(1, $writes);
        $this->assertSame('بازار تست', $options->get('site_title'));
        $this->assertSame('بازار', $options->get('page_title_prefix'));
        $this->assertSame('12', (string) $options->get('max_image_upload_mb'));
        $this->assertDatabaseHas($table, ['key' => 'site_title', 'value' => 'بازار تست']);
    }
}
