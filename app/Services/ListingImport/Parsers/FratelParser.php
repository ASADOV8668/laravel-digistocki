<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;

class FratelParser extends AbstractListingImportParser
{
    public function parse(string $text, ?string $defaultDate = null): array
    {
        $lines = $this->lines($text);
        $items = [];
        $header = null;
        foreach ($lines as $index => $line) {
            if (preg_match('/^‼️?\s*(.+?)\s*‼️?$/u', $line, $match)) {
                $header = trim($match[1]);

                continue;
            }
            if (! $header) {
                continue;
            }
            $withoutBattery = preg_replace('/🔋\s*\d{1,3}%/u', '', $line) ?? $line;
            if (! preg_match('/(?<price>\d{2,4}[.,]\d{3}|\d{5,6})/', $withoutBattery, $match)) {
                continue;
            }
            $battery = $this->battery($line);
            $modelText = preg_match('/\b(?:s\d|a\d|redmi|poco|galaxy)\b/i', $header) ? $header : 'iphone '.$header;
            $positive = str_contains($line, '✅');
            $negative = str_contains($line, '🚫');
            $registered = $positive === $negative ? null : $positive;
            $note = trim(str_replace([$match['price'], '✅', '🚫', '🔋'], '', $withoutBattery));
            $items[] = $this->row($line, $modelText, $this->color($withoutBattery), $this->price($match['price']), $battery, [
                'registered' => $registered,
                'registration_status' => match ($registered) {
                    true => 'رجیستر شده',
                    false => 'رجیستر نشده',
                    null => null,
                },
                'description' => $note,
            ]);
        }

        return $this->result($items, $text, $defaultDate);
    }
}
