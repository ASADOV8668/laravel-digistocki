<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;

class AtKalaParser extends AbstractListingImportParser
{
    private const PRODUCT_LINE = '/^(?<color>[^\pL\pN]+)?(?:iphone\s*)?(?<model>(?:\d{1,2}|xs|max|se).*?)\s+(?<storage>\d{2,4}\s*(?:gb?|tb))\s+(?<part>[a-z]{2,4})\b(?:\s*(?<battery>\d{1,3})\s*(?:bt|%)?)?/iu';

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $lines = $this->lines($text);
        $items = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (! preg_match(self::PRODUCT_LINE, $line, $match)) {
                continue;
            }
            $price = null;
            $raw = $line;
            for ($j = $i + 1; $j < min($i + 4, count($lines)); $j++) {
                if (preg_match(self::PRODUCT_LINE, $lines[$j])) {
                    break;
                }
                if (str_contains($lines[$j], '💰')) {
                    $price = $this->price($lines[$j]);
                    $raw .= "\n".$lines[$j];
                    $i = $j;
                    break;
                }
            }
            $items[] = $this->row($raw, 'iphone '.$match['model'].' '.$match['storage'].' '.$match['part'], $this->color($match['color'] ?? ''), $price, filled($match['battery'] ?? null) ? (int) $match['battery'] : null, ['part_number' => strtoupper($match['part']), 'description' => preg_replace('/^.*?\(([^)]+)\).*$/u', '$1', $raw) !== $raw ? preg_replace('/^.*?\(([^)]+)\).*$/u', '$1', $raw) : null]);
        }

        return $this->result($items, $text, $defaultDate);
    }
}
