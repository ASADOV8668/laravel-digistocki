<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;

class PatternParser extends AbstractListingImportParser
{
    private const FIELDS = [
        'model' => '[^\n]{1,150}?',
        'price' => '[0-9][0-9,.٬،/]{0,14}',
        'color' => '[^\n]{1,40}?',
        'battery' => '[0-9]{1,3}',
        'description' => '[^\n]{1,250}?',
    ];

    public function __construct(private readonly string $pattern) {}

    public function isValid(): bool
    {
        $pattern = $this->clean($this->pattern);
        preg_match_all('/\{([^{}]*)\}/u', $pattern, $matches);
        $fields = $matches[1];

        return in_array('model', $fields, true)
            && in_array('price', $fields, true)
            && count($fields) === count(array_unique($fields))
            && array_diff($fields, array_keys(self::FIELDS)) === []
            && ! preg_match('/[{}]/', preg_replace('/\{[^{}]*\}/u', '', $pattern));
    }

    public function matchesSample(string $sample): bool
    {
        return preg_match($this->expression(), $this->clean($sample)) === 1;
    }

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $text = $this->clean($text);
        preg_match_all($this->expression(), $text, $matches, PREG_SET_ORDER);
        $rows = [];

        foreach ($matches as $match) {
            $price = (int) preg_replace('/\D/', '', $match['price']);
            $color = trim($match['color'] ?? '');
            $battery = isset($match['battery']) ? (int) $match['battery'] : null;

            $rows[] = $this->row($match[0], $match['model'], $color === '' ? null : ($this->color($color) ?? $color),
                $price > 0 ? $price : null, $battery !== null && $battery <= 100 ? $battery : null, [
                    'description' => $match['description'] ?? '',
                ]);
        }

        return $this->result($rows, $text, $defaultDate);
    }

    private function expression(): string
    {
        $parts = preg_split('/\{([a-z]+)\}/', $this->clean($this->pattern), -1, PREG_SPLIT_DELIM_CAPTURE);
        $expression = '';

        foreach ($parts as $index => $part) {
            $expression .= $index % 2 === 0
                ? preg_quote($part, '~')
                : '(?<'.$part.'>'.self::FIELDS[$part].')';
        }

        return '~^[ ]*'.$expression.'[ ]*$~imu';
    }
}
