<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;

class BamaTelParser extends AbstractListingImportParser
{
    public function parse(string $text, ?string $defaultDate = null): array
    {
        $lines = $this->lines($text);
        $items = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (! preg_match('/^(?<prefix>[^\pL\pN]+)?(?<model>(?:iphone\s*)?\d{1,2}.*|iphone.*)$/iu', $line, $match)) {
                continue;
            }
            $rawLines = [$line];
            $details = '';
            $price = null;
            for ($j = $i + 1; $j < min($i + 5, count($lines)); $j++) {
                if ((str_contains($lines[$j], '💵') || str_contains($lines[$j], '💰') || preg_match('~^\s*[\d,./]+\s*$~u', $lines[$j]))
                    && preg_match('~([\d,./]+)~u', $lines[$j], $priceMatch)) {
                    $price = $this->price($priceMatch[1]);
                    $rawLines[] = $lines[$j];
                    $i = $j;
                    break;
                }
                $details .= ' '.trim($lines[$j]);
                $rawLines[] = $lines[$j];
            }
            $modelText = preg_match('/\b(?:s\d|a\d|redmi|poco|galaxy|watch|airpods|ps5|vertu)\b/i', $match['model']) ? $match['model'].' '.$details : 'iphone '.$match['model'].' '.$details;
            $items[] = $this->row(implode("\n", $rawLines), $modelText, $this->color($details) ?? $this->color($match['prefix'] ?? ''), $price, $this->battery($details), [
                'registered' => str_contains($details, '✅'),
                'registration_status' => str_contains($details, '✅') ? 'رجیستر شده' : null,
                'cycle' => preg_match('/cycle\s*:\s*(\d+)/i', $details, $cycle) ? (int) $cycle[1] : null,
                'description' => trim(str_replace(['رجیستر ✅', '✅'], '', $details)),
            ]);
        }

        return $this->result($items, $text, $defaultDate);
    }
}
