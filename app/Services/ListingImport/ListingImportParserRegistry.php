<?php

namespace App\Services\ListingImport;

use App\Models\ListingImportParser as ListingImportParserModel;
use App\Services\ListingImport\Parsers\AtKalaParser;
use App\Services\ListingImport\Parsers\BamaTelParser;
use App\Services\ListingImport\Parsers\FratelParser;

class ListingImportParserRegistry
{
    public function make(ListingImportParserModel $parser): ListingImportParser
    {
        $class = $parser->class_name;
        abort_unless(is_a($class, ListingImportParser::class, true), 500, 'کلاس Parser ایمپورت معتبر نیست.');

        $instance = app($class);

        return in_array($class, [AtKalaParser::class, BamaTelParser::class, FratelParser::class], true)
            ? new GroupedPriceListParser($instance)
            : $instance;
    }
}
