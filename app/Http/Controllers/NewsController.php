<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $category = $request->input('category');

        $articles = Article::published()
            ->when($search !== '', fn ($q) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%")
            ))
            ->when(array_key_exists((string) $category, Article::CATEGORIES), fn ($q) => $q->where('category', $category))
            ->orderByDesc('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('news.index', [
            'articles' => $articles,
            'categories' => Article::CATEGORIES,
            'categoryCounts' => Article::published()
                ->selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category'),
            'popular' => Article::published()->orderByDesc('view_count')->take(5)->get(),
            'search' => $search,
            'category' => $category,
        ]);
    }

    public function show(string $slug)
    {
        $article = Article::published()->with('author')->where('slug', $slug)->firstOrFail();

        // toBase(): tăng lượt xem mà không đổi updated_at của bài
        Article::whereKey($article->id)->toBase()->increment('view_count');
        $article->view_count += 1;

        $related = Article::published()
            ->where('category', $article->category)
            ->whereKeyNot($article->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('news.show', compact('article', 'related'));
    }
}
