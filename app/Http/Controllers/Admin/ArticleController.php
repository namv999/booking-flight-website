<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreArticleRequest;
use App\Http\Requests\Admin\UpdateArticleRequest;
use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $category = $request->input('category');

        $articles = Article::with('author')
            ->when($search, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('title', 'like', "%{$s}%")->orWhere('excerpt', 'like', "%{$s}%")
            ))
            ->when(in_array($status, ['draft', 'published'], true), fn ($q) => $q->where('status', $status))
            ->when(array_key_exists((string) $category, Article::CATEGORIES), fn ($q) => $q->where('category', $category))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.articles.index', [
            'articles' => $articles,
            'search' => $search,
            'status' => $status,
            'category' => $category,
            'categories' => Article::CATEGORIES,
        ]);
    }

    public function create()
    {
        return view('admin.articles.create', ['categories' => Article::CATEGORIES]);
    }

    public function store(StoreArticleRequest $request)
    {
        $data = $this->payload($request);
        $data['slug'] = Article::uniqueSlug($data['title']); // chỉ tạo khi thêm mới
        $data['user_id'] = $request->user()->id;

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail_path'] = $request->file('thumbnail')->store('articles', 'public');
        }

        Article::create($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Thêm bài viết thành công.');
    }

    public function edit(Article $article)
    {
        return view('admin.articles.edit', [
            'article' => $article,
            'categories' => Article::CATEGORIES,
        ]);
    }

    public function update(UpdateArticleRequest $request, Article $article)
    {
        $data = $this->payload($request);

        if ($request->hasFile('thumbnail')) {
            $article->deleteUploadedThumbnail();
            $data['thumbnail_path'] = $request->file('thumbnail')->store('articles', 'public');
        } elseif ($request->boolean('remove_thumbnail')) {
            $article->deleteUploadedThumbnail();
            $data['thumbnail_path'] = null;
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Cập nhật bài viết thành công.');
    }

    public function destroy(Article $article)
    {
        $article->deleteUploadedThumbnail();
        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('success', 'Xóa bài viết thành công.');
    }

    // Dữ liệu chung cho store/update
    private function payload(Request $request): array
    {
        $data = $request->safe()->except(['thumbnail', 'remove_thumbnail']);

        // Checkbox bỏ tích thì không được gửi lên nên phải đọc bằng boolean()
        $data['is_featured'] = $request->boolean('is_featured');

        // Xuất bản: lấy ngày đã nhập (cho phép hẹn giờ) hoặc bây giờ. Nháp: bỏ ngày xuất bản
        $data['published_at'] = $data['status'] === 'published'
            ? ($data['published_at'] ?? now())
            : null;

        return $data;
    }
}
