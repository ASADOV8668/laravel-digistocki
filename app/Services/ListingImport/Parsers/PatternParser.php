<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;
use App\Support\PersianDigits;
use App\Support\RegistrationStatus;

class PatternParser extends AbstractListingImportParser
{
    private const FIELDS = [
        'model' => '[^\n]{1,150}?',
        'price' => '[0-9][0-9,.٬،/]{0,14}',
        'color' => '[^\n]{1,40}?',
        'battery' => '[^\n]{1,40}?',
        'storage' => '[^\n]{1,40}?',
        'registration' => '[^\n]{1,60}?',
        'markers' => '[^\n]{1,250}?',
        'description' => '[^\n]{1,250}?',
    ];

    public const SYMBOL_FIELDS = ['registration', 'storage', 'color', 'battery'];

    public function __construct(private readonly string $pattern, private readonly array $symbols = []) {}

    public static function normalizeSymbol(string $symbol): string
    {
        return mb_strtolower(str_replace(["\u{FE0E}", "\u{FE0F}"], '', PersianDigits::toEnglish(trim($symbol))));
    }

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
            $markerText = preg_replace('/(?<=\d) +(?=(?:گیگ(?:ابایت)?|ترابایت|gb?|tb?|bt|[%٪])(?![\pL\pN]))/iu', '', $match['markers'] ?? '');
            $markers = preg_split('/\s+/u', trim($markerText), -1, PREG_SPLIT_NO_EMPTY);
            $registration = $this->field('registration', $match, $markers);
            $extra = [
                'storage' => $this->field('storage', $match, $markers) ?? $this->storageFromModel($match['model']),
                'description' => $match['description'] ?? '',
            ];
            if ($registration !== null) {
                $extra['registered'] = $registration === '1';
            }
            $details = RegistrationStatus::detect($match['registration'] ?? $match[0]);
            $extra['registered'] ??= RegistrationStatus::registered($details);
            $extra['registration_status'] = RegistrationStatus::registered($details) === $extra['registered']
                ? $details : RegistrationStatus::fromBoolean($extra['registered']);

            $rows[] = $this->row($match[0], $match['model'], $this->field('color', $match, $markers),
                $price > 0 ? $price : null, $this->field('battery', $match, $markers), $extra);
        }

        return $this->result($rows, $text, $defaultDate);
    }

    private function field(string $field, array $match, array $markers): string|int|null
    {
        $explicit = isset($match[$field]);
        $values = [];
        foreach ($explicit ? [$match[$field]] : $markers as $marker) {
            $symbol = self::normalizeSymbol($marker);
            $configured = $this->symbols[$field][$symbol] ?? null;
            if (! $explicit && $configured === null && array_filter($this->symbols, fn (array $aliases): bool => isset($aliases[$symbol]))) {
                continue;
            }
            $value = match ($field) {
                'registration' => $configured ?? match ($symbol) {
                    '✅', 'رجیستر شده' => '1',
                    '❌', 'رجیستر نشده' => '0',
                    default => null,
                },
                'storage' => $configured !== null ? (int) $configured : $this->storageValue($symbol, $explicit),
                'battery' => $configured !== null ? (int) $configured : $this->batteryValue($symbol, $explicit),
                'color' => $configured ?? ($explicit || preg_match('/^\p{Extended_Pictographic}$/u', $symbol) ? ($this->color($marker) ?? $this->color($symbol)) : null)
                    ?? ($explicit ? trim($marker) : null),
            };
            if ($value !== null) {
                $values[] = $value;
            }
        }

        $values = array_unique($values, SORT_REGULAR);

        return count($values) === 1 ? reset($values) : null;
    }

    private function storageValue(string $symbol, bool $explicit): ?int
    {
        if ($explicit && preg_match('/^[1-9][0-9]{0,3}$/D', $symbol)) {
            return (int) $symbol;
        }

        return preg_match('/^([1-9][0-9]{0,3})\s*(گیگ(?:ابایت)?|ترابایت)$/uD', $symbol, $parts)
            ? (int) $parts[1] * ($parts[2] === 'ترابایت' ? 1024 : 1)
            : $this->storage($symbol);
    }

    private function storageFromModel(string $model): ?int
    {
        if (preg_match('/\s+(\d{1,4}\s*(?:گیگ(?:ابایت)?|ترابایت))$/uD', $model, $parts)) {
            return $this->storageValue($parts[1], true);
        }

        return null;
    }

    private function batteryValue(string $symbol, bool $explicit): ?int
    {
        $pattern = $explicit ? '/^(\d{1,3})(?:\s*(?:%|٪|bt))?$/D' : '/^(\d{1,3})\s*(?:%|٪|bt)$/D';

        return preg_match($pattern, $symbol, $parts) && (int) $parts[1] <= 100 ? (int) $parts[1] : null;
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
