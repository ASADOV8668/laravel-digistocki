<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListingImportParser;
use Illuminate\Http\Request;

class ListingImportParserController extends Controller
{
    public function index(Request $request)
    {
        $parsers = ListingImportParser::query()->orderBy('title')->get();
        $selected = $request->filled('parser')
            ? $parsers->firstWhere('key', $request->string('parser')->toString())
            : $parsers->first();

        return view('admin/listing-imports/index', compact('parsers', 'selected'));
    }
}
