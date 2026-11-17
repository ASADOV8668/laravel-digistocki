<?php

namespace App\Services\ListingImport;

use App\Models\ListingImportParser as ListingImportParserModel;

class ListingImportParserRegistry
{
    public function make(ListingImportParserModel $parser): ListingImportParser
    {
        $class = $parser->class_name;
        abort_unless(is_a($class, ListingImportParser::class, true), 500, 'کلاس Parser ایمپورت معتبر نیست.');

        return app($class);
    }
}
