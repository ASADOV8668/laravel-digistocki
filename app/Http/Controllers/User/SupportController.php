<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\SystemOptions;

class SupportController extends Controller
{
    public function index(SystemOptions $options)
    {
        return view('support.index', ['settings' => $options->all()]);
    }
}
