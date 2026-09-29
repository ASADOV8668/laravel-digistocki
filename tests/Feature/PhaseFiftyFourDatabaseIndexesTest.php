<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseFiftyFourDatabaseIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_feed_and_eav_filter_indexes_are_present(): void
    {
        $listingIndexes = collect(Schema::getIndexes('listings'))->pluck('name');
        $attributeIndexes = collect(Schema::getIndexes('listing_attribute_values'))->pluck('name');

        $this->assertTrue($listingIndexes->contains('listings_public_feed_index'));
        $this->assertTrue($attributeIndexes->contains('listing_attribute_string_filter_index'));
        $this->assertTrue($attributeIndexes->contains('listing_attribute_integer_filter_index'));
        $this->assertTrue($attributeIndexes->contains('listing_attribute_decimal_filter_index'));
        $this->assertTrue($attributeIndexes->contains('listing_attribute_boolean_filter_index'));
    }
}
