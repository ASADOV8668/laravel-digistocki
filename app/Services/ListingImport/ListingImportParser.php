<?php

namespace App\Services\ListingImport;

interface ListingImportParser
{
    /**
     * @return array{items: array<int, array<string, mixed>>, detected_date: ?string, date_required: bool}
     */
    public function parse(string $text, ?string $defaultDate = null): array;
}
