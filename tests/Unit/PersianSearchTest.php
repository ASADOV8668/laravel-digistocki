<?php

namespace Tests\Unit;

use App\Support\PersianSearch;
use PHPUnit\Framework\TestCase;

class PersianSearchTest extends TestCase
{
    public function test_persian_variants_are_included_in_search_likes(): void
    {
        $likes = PersianSearch::likes('ایفون');

        $this->assertContains('%آیفون%', $likes);
        $this->assertContains('%ایفون%', $likes);
    }

    public function test_persian_search_keeps_english_model_numbers(): void
    {
        $likes = PersianSearch::likes('iPhone 13');

        $this->assertContains('%iPhone 13%', $likes);
    }
}
