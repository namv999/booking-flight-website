@extends('layouts.admin')

@section('title', 'Quản lý Tin tức - Jet Charter Flights')

@section('content')
@vite(['resources/css/news.css'])
<div class="container-fluid px-0 bf-admin-articles">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <span class="bf-eyebrow">Nội dung website</span>
            <h1 class="h3 fw-bold text-dark mb-1">Quản lý Tin tức</h1>
            <p class="text-muted small mb-0">Soạn, cập nhật và xuất bản bài viết cho khách hàng.</p>
        </div>
        <a href="{{ route('admin.articles.create') }}" class="btn btn-primary d-inline-flex align-items-center"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Thêm bài viết</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.articles.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-lg-6">
                    <label class="form-label small fw-semibold" for="article-search">Tìm bài viết</label>
                    <input id="article-search" class="form-control" type="search" name="search" value="{{ $search ?? '' }}" placeholder="Tìm theo tiêu đề hoặc mô tả">
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold" for="article-status">Trạng thái</label>
                    <select id="article-status" class="form-select" name="status">
                        <option value="">Tất cả</option>
                        <option value="draft" @selected(($status ?? '') === 'draft')>Nháp</option>
                        <option value="published" @selected(($status ?? '') === 'published')>Đã xuất bản</option>
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <label class="form-label small fw-semibold" for="article-category">Chuyên mục</label>
                    <select id="article-category" class="form-select" name="category">
                        <option value="">Tất cả</option>
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}" @selected(($category ?? '') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-lg-2 d-flex gap-2">
                    <button class="btn btn-primary flex-grow-1" type="submit">Lọc</button>
                    @if (($search ?? '') !== '' || ($status ?? '') !== '' || ($category ?? '') !== '')
                        <a class="btn btn-outline-secondary" href="{{ route('admin.articles.index') }}" title="Xóa bộ lọc" aria-label="Xóa bộ lọc"><i class="bi bi-x-lg" aria-hidden="true"></i></a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 bf-articles-table">
                <thead class="table-light">
                    <tr><th class="ps-3">Ảnh</th><th>Bài viết</th><th>Chuyên mục</th><th>Trạng thái</th><th>Lượt xem</th><th>Ngày xuất bản</th><th class="text-end pe-3">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($articles as $article)
                        <tr>
                            <td class="ps-3"><img class="bf-admin-article-thumb" src="{{ $article->thumbnail_url }}" alt=""></td>
                            <td class="bf-admin-article-title">
                                @if ($article->status === 'published')
                                    <a href="{{ route('news.show', $article->slug) }}" target="_blank" rel="noopener noreferrer">{{ $article->title }}</a>
                                @else
                                    <strong>{{ $article->title }}</strong>
                                @endif
                                @if ($article->is_featured)<span class="text-warning ms-1" title="Ghim trang chủ"><i class="bi bi-star-fill" aria-label="Nổi bật"></i></span>@endif
                                <small class="d-block text-muted">{{ $article->author?->name ?? 'Không có tác giả' }}</small>
                            </td>
                            <td><span class="badge text-bg-light border">{{ $article->category_label }}</span></td>
                            <td><span class="badge {{ $article->status === 'published' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $article->status === 'published' ? 'Đã xuất bản' : 'Nháp' }}</span></td>
                            <td>{{ number_format($article->view_count) }}</td>
                            <td>{{ $article->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm" role="group" aria-label="Thao tác bài viết">
                                    <a class="btn btn-outline-secondary" href="{{ route('admin.articles.edit', $article) }}" aria-label="Sửa {{ $article->title }}" title="Chỉnh sửa"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xóa bài viết này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger" type="submit" aria-label="Xóa {{ $article->title }}" title="Xóa"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-journal-text fs-2 d-block mb-2" aria-hidden="true"></i>Chưa có bài viết phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($articles->hasPages())<div class="p-3 border-top d-flex justify-content-center">{{ $articles->links() }}</div>@endif
    </div>
</div>
@endsection
