<?php

namespace App\Services\ListingImport;

use App\Models\ListingImportParser as ListingImportParserModel;
use App\Services\ListingImport\Parsers\AtKalaParser;
use App\Services\ListingImport\Parsers\BamaTelParser;
use App\Services\ListingImport\Parsers\FratelParser;
use App\Services\ListingImport\Parsers\PatternParser;
use App\Services\ListingImport\Parsers\UnifiedParser;

class ListingImportParserRegistry
{
    public function make(ListingImportParserModel $parser): ListingImportParser
    {
        $class = $parser->class_name;
        if ($parser->pattern !== null) {
            return new PatternParser($parser->pattern, $parser->symbol_map ?? []);
        }

        if (in_array($class, [AtKalaParser::class, BamaTelParser::class, FratelParser::class], true)) {
            $class = UnifiedParser::class;
        }
        if ($class === UnifiedParser::class) {
            return new UnifiedParser(($parser->symbol_map ?? [])['color'] ?? []);
        }

        abort_unless(is_a($class, ListingImportParser::class, true), 500, 'کلاس Parser ایمپورت معتبر نیست.');

        return app($class);
    }
}
