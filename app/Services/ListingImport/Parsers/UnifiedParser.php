<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;
use App\Support\RegistrationStatus;

class UnifiedParser extends AbstractListingImportParser
{
    private const MODEL = '/^(?:[\p{Extended_Pictographic}\x{FE0F}\s]+)?(?<model>(?:apple\s+)?(?:(?:iphone|آیفون|ایفون)\s*)?(?:\d{1,2}(?!\d|[.,\/%٪])(?=\s|$|pro|plus|mini|normal|max)|xs(?:\s*max)?\b|xr\b|se\b|duo\b).*|(?:galaxy|samsung|xiaomi|redmi|poco)\b.*)/iu';

    private const PRICE = '/(?<!\d)(\d{1,4}(?:[.,٬،\/]\d{3}){1,3}|\d{5,12})(?:\s*(m|تومان))?(?!\d)/iu';

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $items = [];
        $header = null;
        $record = null;
        $priceBlock = null;

        foreach ($this->lines($this->clean($text)) as $line) {
            if (str_starts_with($line, '📱') && str_contains($line, '|') && preg_match(self::MODEL, $line, $model) && $this->linePrice($line) === null) {
                $this->appendRecord($items, $record);
                $record = $header = null;
                $priceBlock = ['model' => $model['model'], 'heading' => $line, 'activation' => null, 'status_line' => null];
                continue;
            }
            if ($priceBlock !== null && $this->readPriceBlock($items, $priceBlock, $line)) {
                continue;
            }
            $priceBlock = null;

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

    private function readPriceBlock(array &$items, array &$block, string $line): bool
    {
        if (preg_match('/^(?:[🔒🔓]\x{FE0F}?\s*)?(not\s+active|active)$/iu', $line, $status)) {
            $block['activation'] = mb_strtolower($status[1]) === 'active' ? 'Active' : 'NotACT';
            $block['status_line'] = $line;

            return true;
        }

        $pairs = [];
        foreach (explode('|', $line) as $segment) {
            $segment = trim($segment);
            if (! preg_match('/^\p{Extended_Pictographic}\x{FE0F}?\h*\d/u', $segment) || ($price = $this->linePrice($segment)) === null) {
                return false;
            }
            $pairs[] = [$segment, $price];
        }
        foreach ($pairs as [$segment, $price]) {
            $this->appendRecord($items, [
                'model' => $block['model'], 'lines' => array_filter([$block['heading'], $block['status_line'], $segment]), 'price' => $price,
                'activation' => $block['activation'], 'color' => $this->color($segment),
            ]);
        }

        return true;
    }

    private function appendRecord(array &$items, ?array $record): void
    {
        if ($record === null) {
            return;
        }

        $raw = implode("\n", $record['lines']);
        $model = $record['model'];
        $modelText = preg_match('/^(?:galaxy|samsung|xiaomi|redmi|poco)\b/iu', $model)
            ? $model
            : 'iphone '.strtr(preg_replace('/^(?:apple\s+)?(?:(?:iphone|آیفون|ایفون)\s*)?/iu', '', $model), [
                'پرو مکس' => 'Pro Max', 'پرومکس' => 'Pro Max', 'پرو' => 'Pro', 'پلاس' => 'Plus', 'مینی' => 'Mini',
            ]);
        $details = str_replace(["\u{FE0E}", "\u{FE0F}"], '', $raw);
        $activation = $record['activation'] ?? null;
        if ($activation === null && preg_match('/\b(not\s+active|not|active)\h*\$/iu', $details, $status)) {
            $activation = mb_strtolower($status[1]) === 'active' ? 'Active' : 'NotACT';
        }
        $registration = RegistrationStatus::detect($details);
        $part = preg_match('/\b(ZAA|ZA|CHA|CH|LLA|LL|JPA|JAP|JP|AE|AH|HN|B|KH|TA|TH|VN)\b/i', $model.' '.$details, $partNumber)
            ? strtoupper($partNumber[1]) : null;
        $storage = preg_match('/(?<!\d)(\d{2,4})\s*(گیگ(?:ابایت)?|ترابایت)(?![\pL\pN])/u', $details, $capacity)
            ? (int) $capacity[1] * ($capacity[2] === 'ترابایت' ? 1024 : 1)
            : $this->storage($details);
        if ($storage === null && preg_match('/\s+(32|64|128|256|512|1024|2048)(?!\d|[.,\/%٪])(?=\s|$)/u', $model, $capacity)) {
            $storage = (int) $capacity[1];
        }

        preg_match_all('/\(([^()]*[\pL][^()]*)\)/u', $details, $notes);
        $color = array_key_exists('color', $record) ? $record['color'] : ($this->color($details) ?? $this->color($model));
        $items[] = $this->row($raw, $modelText, $color, $record['price'], $this->battery($details), [
            'storage' => $storage,
            'registered' => RegistrationStatus::registered($registration),
            'registration_status' => $registration,
            'part_number' => $part,
            'activation_status' => $activation,
            'cycle' => preg_match('/\bcycle\s*:?\s*(\d+)/i', $details, $cycle) ? (int) $cycle[1] : null,
            'description' => implode(' ', $notes[1]),
        ]);
    }

    private function linePrice(string $line): ?int
    {
        $candidate = preg_replace('/(?:🔋\s*\d{1,3}(?:[%٪]|\s*bt)?|\d{1,3}\s*(?:[%٪]|bt|🔋))/iu', '', $line);
        if (preg_match('/(?:💵|💰|قیمت|\bprice\b)\s*[:=：-]?\s*(.+)/iu', $line, $label)) {
            if (preg_match('/^(\d{1,4}(?:[.٫]\d{1,3})?)\h*(هزار|میلیون|میلیارد)(?!\pL)/u', $label[1], $scaled)) {
                $multiplier = match ($scaled[2]) {
                    'هزار' => 1000,
                    'میلیون' => 1000000,
                    'میلیارد' => 1000000000,
                };

                return (int) round((float) str_replace('٫', '.', $scaled[1]) * $multiplier);
            }
            $candidate = preg_replace('/(?<=\d)\h+(?=\d{3}(?!\d))/u', ',', $label[1]);
        }
        if (! preg_match(self::PRICE, $candidate, $price)) {
            return null;
        }

        $amount = (int) preg_replace('/\D/', '', $price[1]);

        $unit = mb_strtolower($price[2] ?? '');
        $legacyGrouping = preg_match('/^\d{4}[.,\/]\d{3}$/D', $price[1]);

        return $unit === 'm' || ($unit !== 'تومان' && ($amount < 1000000 || $legacyGrouping)) ? $amount * 1000 : $amount;
    }
}
