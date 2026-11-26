<?php

namespace App\Services\ListingImport;

use App\Services\ListingImport\Parsers\UnifiedParser;

class GroupedPriceListParser implements ListingImportParser
{
    public function __construct(private readonly ListingImportParser $fallback) {}

    public function parse(string $text, ?string $defaultDate = null): array
    {
        $parser = UnifiedParser::supportsGroupedPrices($text) ? new UnifiedParser : $this->fallback;

        return $parser->parse($text, $defaultDate);
    }
}
