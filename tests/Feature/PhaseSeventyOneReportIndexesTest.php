<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSeventyOneReportIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_moderation_feed_index_is_present(): void
    {
        $indexes = collect(Schema::getIndexes('reports'))->pluck('name');

        $this->assertTrue($indexes->contains('reports_moderation_feed_index'));
    }
}
