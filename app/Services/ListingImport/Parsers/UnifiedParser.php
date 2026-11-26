<?php

namespace App\Services\ListingImport\Parsers;

use App\Services\ListingImport\AbstractListingImportParser;
use App\Support\PersianDigits;
use App\Support\RegistrationStatus;

class UnifiedParser extends AbstractListingImportParser
{
    private const EMOJI = '(?:(?=[\p{Extended_Pictographic}\p{Regional_Indicator}]|[0-9#*]\x{FE0F}?\x{20E3})\X)';

    private const MODEL = '/^(?:'.self::EMOJI.'|\s)*(?<model>(?:apple\s+)?(?:(?:iphone|آیفون|ایفون)\s*)?(?:\d{1,2}(?!\d|[.,\/%٪])(?=\s|$|pro|plus|mini|normal|max)|xs(?:\s*max)?\b|xr\b|se\b|duo\b).*|(?:galaxy|samsung|xiaomi|redmi|poco)\b.*)/iu';

    private const PRICE = '/(?<!\d)(\d{1,4}(?:[.,٬،\/]\d{3}){1,3}|\d{5,12})(?:\s*(m|تومان))?(?!\d)/iu';

    private const CAPACITY = '\d{1,4}\h*(?:[gt]b?|گیگ(?:ابایت)?|ترابایت)(?![\pL\pN])';

    private const CAPACITY_HEADING = '/^(?:'.self::EMOJI.'|\h)*'.self::CAPACITY.'(?=\h*(?:\||$))/iu';

    private const PART = '/\b(ZAA|ZA|CHA|CH|LLA|LL|JPA|JAP|JP|AE|AH|HN|B|KH|TA|TH|VN)\b/i';

    public function __construct(private readonly array $colorSymbols = []) {}

    public static function supportsGroupedPrices(string $text): bool
    {
        $hasModel = false;
        $priceLines = 0;
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim(PersianDigits::toEnglish($line));
            if (preg_match(self::MODEL, $line)) {
                if (str_contains($line, '|') && ! preg_match(self::PRICE, $line)) {
                    return true;
                }
                $hasModel = true;
                $priceLines = 0;
            } elseif ($hasModel) {
                if (preg_match(self::CAPACITY_HEADING, $line)
                    || (preg_match('/^'.self::EMOJI.'\h*\d/u', $line) && preg_match(self::PRICE, $line) && ++$priceLines > 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $items = [];
        $header = null;
        $headerActivation = null;
        $record = null;
        $priceBlock = null;

        foreach ($this->lines($this->clean($text)) as $line) {
            if (str_contains($line, '|') && preg_match(self::MODEL, $line, $model) && $this->linePrice($line) === null) {
                if (($record['price'] ?? null) !== null) {
                    $this->appendRecord($items, $record);
                }
                $record = $header = null;
                $headerActivation = null;
                $priceBlock = $this->priceBlock($model['model'], $line);

                continue;
            }
            if ($priceBlock === null && $header !== null && preg_match(self::CAPACITY_HEADING, $line)) {
                if (($record['price'] ?? null) !== null) {
                    $this->appendRecord($items, $record);
                }
                $priceBlock = $this->priceBlock($header, $header);
                $priceBlock['activation'] ??= $headerActivation;
                $record = $header = $headerActivation = null;
            }
            if ($priceBlock !== null && $this->readPriceBlock($items, $priceBlock, $line)) {
                continue;
            }
            $priceBlock = null;

            $heading = preg_match('/^‼️?\s*(.*?)\s*‼️?$/u', $line, $title);
            $modelLine = preg_match(self::MODEL, $heading ? $title[1] : $line, $model);
            $price = $heading ? null : $this->linePrice($line);

            if (! $modelLine && $header !== null && ($activation = $this->activationLine($line)) !== null) {
                if (($record['price'] ?? null) !== null) {
                    $this->appendRecord($items, $record);
                    $record = null;
                }
                $headerActivation = $activation;
                if ($record !== null) {
                    $record['activation'] = $activation;
                    $record['lines'][] = $line;
                }

                continue;
            }

            if ($modelLine || ($price !== null && $record !== null && $record['price'] !== null)) {
                $this->appendRecord($items, $record);
                $record = null;
            }

            if ($modelLine) {
                $header = $heading ? $title[1] : $model['model'];
                $headerActivation = null;
                if ($heading) {
                    continue;
                }
                $record = ['model' => $model['model'], 'lines' => [$line], 'price' => $price];
            } elseif ($record !== null) {
                $record['lines'][] = $line;
                $record['price'] ??= $price;
            } elseif ($header !== null && $price !== null) {
                $record = ['model' => $header, 'lines' => [$line], 'price' => $price, 'activation' => $headerActivation];
            }
        }

        $this->appendRecord($items, $record);

        return $this->result($items, $text, $defaultDate);
    }

    private function readPriceBlock(array &$items, array &$block, string $line): bool
    {
        if (($activation = $this->activationLine($line)) !== null) {
            $block['activation'] = $activation;
            $block['status_line'] = $line;

            return true;
        }

        if (preg_match(self::CAPACITY_HEADING, $line)) {
            $model = preg_split('/\h*\|\h*|\h+(?='.self::CAPACITY.')/iu', $block['model'], 2)[0];
            if (! preg_match(self::PART, $line) && preg_match(self::PART, $block['model'], $previousPart)) {
                $model .= ' '.$previousPart[1];
            }
            $next = $this->priceBlock($model.' '.$line, $line);
            $next['activation'] ??= $block['activation'];
            $block = $next;

            return true;
        }

        if (preg_match('/^[─━═—–_-]{2,}$/u', $line)) {
            return true;
        }

        $pairs = [];
        foreach (explode('|', $line) as $segment) {
            $segment = trim($segment);
            if (! preg_match('/^'.self::EMOJI.'\h*\d/u', $segment) || ($price = $this->linePrice($segment)) === null) {
                return false;
            }
            $pairs[] = [$segment, $price];
        }
        foreach ($pairs as [$segment, $price]) {
            $this->appendRecord($items, [
                'model' => $block['model'], 'lines' => array_filter([$block['heading'], $block['status_line'], $segment]), 'price' => $price,
                'activation' => $block['activation'], 'color' => $this->color($segment), 'color_source' => $segment,
            ]);
        }

        return true;
    }

    private function priceBlock(string $model, string $heading): array
    {
        $fields = explode('|', $heading);
        $status = trim((string) end($fields));

        return [
            'model' => $model,
            'heading' => $heading,
            'activation' => $this->activationLine($status),
            'status_line' => null,
        ];
    }

    private function activationLine(string $line): ?string
    {
        if (! preg_match('/^(?:'.self::EMOJI.'|[▫▪•◦\h\x{FE0F}])*(?<status>not\h*act(?:ive)?|nat|act(?:ive)?|نات?[\h\x{200C}]*اکتیو|اکتیو)\h*$/iu', $line, $match)) {
            return null;
        }

        return in_array(mb_strtolower($match['status']), ['act', 'active', 'اکتیو'], true) ? 'Active' : 'NotACT';
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
        $part = preg_match(self::PART, $model.' '.$details, $partNumber)
            ? strtoupper($partNumber[1]) : null;
        $storage = preg_match('/(?<!\d)(\d{1,4})\s*(گیگ(?:ابایت)?|ترابایت)(?![\pL\pN])/u', $model.' '.$details, $capacity)
            ? (int) $capacity[1] * ($capacity[2] === 'ترابایت' ? 1024 : 1)
            : $this->storage($model.' '.$details);
        if ($storage === null && preg_match('/\s+(32|64|128|256|512|1024|2048)(?!\d|[.,\/%٪])(?=\s|$)/u', $model, $capacity)) {
            $storage = (int) $capacity[1];
        }

        preg_match_all('/\(([^()]*[\pL][^()]*)\)/u', $details, $notes);
        $configuredColor = $this->configuredColor($record['color_source'] ?? $details);
        $color = $configuredColor ?? (array_key_exists('color', $record) ? $record['color'] : ($this->color($details) ?? $this->color($model)));
        $items[] = $this->row($raw, $modelText, $color, $record['price'], $this->battery($details), [
            'storage' => $storage,
            'registered' => RegistrationStatus::registered($registration),
            'registration_status' => $registration,
            'part_number' => $part,
            'activation_status' => $activation,
            'configured_color' => $configuredColor,
            'cycle' => preg_match('/\bcycle\s*:?\s*(\d+)/i', $details, $cycle) ? (int) $cycle[1] : null,
            'description' => implode(' ', $notes[1]),
        ]);
    }

    private function configuredColor(string $text): ?string
    {
        if ($this->colorSymbols === []) {
            return null;
        }
        preg_match_all('/\X/u', $text, $tokens);
        foreach ($tokens[0] as $token) {
            $symbol = mb_strtolower(str_replace(["\u{FE0E}", "\u{FE0F}"], '', PersianDigits::toEnglish(trim($token))));
            $color = $this->colorSymbols[$symbol] ?? null;
            if ($color !== null) {
                return $color;
            }
        }

        return null;
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
