<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;

class UnifiedParser extends AbstractListingImportParser
{
    private const MODEL = '/^(?:[\p{Extended_Pictographic}\x{FE0F}\s]+)?(?<model>(?:iphone\s*)?(?:\d{1,2}(?!\d|[.,\/%٪])(?=\s|$|pro|plus|mini|normal|max)|xs(?:\s*max)?\b|xr\b|se\b).*|(?:galaxy|samsung|xiaomi|redmi|poco)\b.*)/iu';

    private const PRICE = '/(?<!\d)(\d{1,4}(?:[.,٬،\/]\d{3}){1,3}|\d{5,12})(?:\s*(m|تومان))?(?!\d)/iu';

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $items = [];
        $header = null;
        $record = null;

        foreach ($this->lines($this->clean($text)) as $line) {
            $heading = preg_match('/^‼️?\s*(.*?)\s*‼️?$/u', $line, $title);
            $modelLine = preg_match(self::MODEL, $heading ? $title[1] : $line, $model);
            $price = $heading ? null : $this->linePrice($line);

            if ($modelLine || ($price !== null && $record !== null && $record['price'] !== null)) {
                $this->appendRecord($items, $record);
                $record = null;
            }

            if ($modelLine) {
                $header = $heading ? $title[1] : null;
                if ($heading) {
                    continue;
                }
                $record = ['model' => $model['model'], 'lines' => [$line], 'price' => $price];
            } elseif ($record !== null) {
                $record['lines'][] = $line;
                $record['price'] ??= $price;
            } elseif ($header !== null && $price !== null) {
                $record = ['model' => $header, 'lines' => [$line], 'price' => $price];
            }
        }

        $this->appendRecord($items, $record);

        return $this->result($items, $text, $defaultDate);
    }

    private function appendRecord(array &$items, ?array $record): void
    {
        if ($record === null) {
            return;
        }

        $raw = implode("\n", $record['lines']);
        $model = $record['model'];
        $modelText = preg_match('/^(?:galaxy|samsung|xiaomi|redmi|poco)\b/iu', $model) ? $model : 'iphone '.$model;
        $details = str_replace(["\u{FE0E}", "\u{FE0F}"], '', $raw);
        $positive = (bool) preg_match('/✅|رجیستر(?:ی)?\s+شده/u', $details);
        $negative = (bool) preg_match('/❌|🚫|رجیستر(?:ی)?\s+نشده/u', $details);
        $part = preg_match('/\b(ZAA|ZA|CH|LLA|LL|JPA|JAP|JP|AE|AH|HN|B|KH|TA|TH|VN)\b/i', $model, $partNumber)
            ? strtoupper($partNumber[1]) : null;
        $storage = preg_match('/(?<!\d)(\d{2,4})\s*(گیگ(?:ابایت)?|ترابایت)(?![\pL\pN])/u', $details, $capacity)
            ? (int) $capacity[1] * ($capacity[2] === 'ترابایت' ? 1024 : 1)
            : $this->storage($details);
        if ($storage === null && preg_match('/\s+(32|64|128|256|512|1024|2048)(?!\d|[.,\/%٪])(?=\s|$)/u', $model, $capacity)) {
            $storage = (int) $capacity[1];
        }

        preg_match_all('/\(([^()]*[\pL][^()]*)\)/u', $details, $notes);
        $items[] = $this->row($raw, $modelText, $this->color($details) ?? $this->color($model), $record['price'], $this->battery($details), [
            'storage' => $storage,
            'registered' => $positive === $negative ? null : $positive,
            'part_number' => $part,
            'cycle' => preg_match('/\bcycle\s*:?\s*(\d+)/i', $details, $cycle) ? (int) $cycle[1] : null,
            'description' => implode(' ', $notes[1]),
        ]);
    }

    private function linePrice(string $line): ?int
    {
        $candidate = preg_replace('/(?:🔋\s*\d{1,3}(?:[%٪]|\s*bt)?|\d{1,3}\s*(?:[%٪]|bt|🔋))/iu', '', $line);
        if (! preg_match(self::PRICE, $candidate, $price)) {
            return null;
        }

        $amount = (int) preg_replace('/\D/', '', $price[1]);

        $unit = mb_strtolower($price[2] ?? '');
        $legacyGrouping = preg_match('/^\d{4}[.,\/]\d{3}$/D', $price[1]);

        return $unit === 'm' || ($unit !== 'تومان' && ($amount < 1000000 || $legacyGrouping)) ? $amount * 1000 : $amount;
    }
}
