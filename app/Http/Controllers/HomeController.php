<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\FareClass;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $airports = Airport::orderBy('city')->get();
        $fareClasses = FareClass::orderBy('base_price')->get();

        return view('home', compact('airports', 'fareClasses'));
    }
}