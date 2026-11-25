<?php

namespace App\Services\ListingImport;

use App\Models\ListingImportParser as ListingImportParserModel;
use App\Services\ListingImport\Parsers\PatternParser;

class ListingImportParserRegistry
{
    public function make(ListingImportParserModel $parser): ListingImportParser
    {
        $class = $parser->class_name;
        if ($parser->pattern !== null) {
            return new PatternParser($parser->pattern);
        }

        abort_unless(is_a($class, ListingImportParser::class, true), 500, 'کلاس Parser ایمپورت معتبر نیست.');

        return app($class);
    }
}
