<?php

namespace Tests\Unit;

use App\Support\PersianDigits;
use PHPUnit\Framework\TestCase;

class PersianDigitsTest extends TestCase
{
    public function test_persian_and_arabic_digits_are_converted_recursively_without_changing_text(): void
    {
        $value = PersianDigits::normalizeRecursive([
            'address' => 'بابلسر، پلاک ۱۲',
            'nested' => ['mobile' => '۰۹۱۲۳۴۵۶۷۸۹'],
        ]);

        $this->assertSame('بابلسر، پلاک 12', $value['address']);
        $this->assertSame('09123456789', $value['nested']['mobile']);
    }
}
