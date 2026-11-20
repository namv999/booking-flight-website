<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use App\Models\Article;
use App\Models\FareClass;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $airports = Airport::orderBy('city')->get();
        $fareClasses = FareClass::orderBy('base_price')->get();

        // Tối đa 9 bài, bài ghim lên đầu
        $homeArticles = Article::published()
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->take(9)
            ->get();

        // Chỉ các chuyên mục đang có bài, dùng làm tab
        $homeCategories = collect(Article::CATEGORIES)
            ->only($homeArticles->pluck('category')->unique()->all());

        return view('home', compact('airports', 'fareClasses', 'homeArticles', 'homeCategories'));
    }
}
