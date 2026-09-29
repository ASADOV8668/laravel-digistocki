<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseSeventyTwoFavoriteIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_favorites_feed_index_is_present(): void
    {
        $indexes = collect(Schema::getIndexes('favorites'))->pluck('name');

        $this->assertTrue($indexes->contains('favorites_user_feed_index'));
    }
}
