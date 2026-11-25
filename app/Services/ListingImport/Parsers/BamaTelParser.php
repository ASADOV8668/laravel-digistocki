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
            if (! preg_match('/^(?:📱\s*)?(?<model>(?:iphone\s*)?\d{1,2}.*|iphone.*)$/iu', $line, $match)) {
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
                    if (isset($lines[$j + 1]) && preg_match('/^رجیستر(?:ی)?\s*(?:شده|نشده|✅|❌|🚫)/u', $lines[$j + 1])) {
                        $details .= ' '.$lines[++$i];
                        $rawLines[] = $lines[$i];
                    }
                    break;
                }
                $details .= ' '.trim($lines[$j]);
                $rawLines[] = $lines[$j];
            }
            $modelText = preg_match('/\b(?:s\d|a\d|redmi|poco|galaxy|watch|airpods|ps5|vertu)\b/i', $match['model']) ? $match['model'].' '.$details : 'iphone '.$match['model'].' '.$details;
            $positive = (bool) preg_match('/✅|رجیستر(?:ی)?\s+شده/u', $details);
            $negative = (bool) preg_match('/❌|🚫|رجیستر(?:ی)?\s+نشده/u', $details);
            $registered = $positive === $negative ? null : $positive;
            $items[] = $this->row(implode("\n", $rawLines), $modelText, $this->color($details), $price, $this->battery($details), [
                'registered' => $registered,
                'registration_status' => match ($registered) {
                    true => 'رجیستر شده',
                    false => 'رجیستر نشده',
                    null => null,
                },
                'cycle' => preg_match('/cycle\s*:\s*(\d+)/i', $details, $cycle) ? (int) $cycle[1] : null,
                'description' => trim(str_replace(['رجیستر ✅', '✅'], '', $details)),
            ]);
        }

        return $this->result($items, $text, $defaultDate);
    }
}
