<?php

namespace App\Http\Controllers\Addons;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AddressController extends Controller
{
    public function index(): View
    {
        return view('addons.address.index', [
            'config' => config('addons.address'),
        ]);
    }
}
