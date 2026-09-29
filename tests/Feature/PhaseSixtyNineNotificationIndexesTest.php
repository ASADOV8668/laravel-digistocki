<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSixtyNineNotificationIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_lookup_indexes_are_present(): void
    {
        $indexes = collect(Schema::getIndexes('notifications'))->pluck('name');

        $this->assertTrue($indexes->contains('notifications_unread_lookup_index'));
        $this->assertTrue($indexes->contains('notifications_feed_lookup_index'));
    }
}
