@extends('layouts.app')

@section('title', 'Tin tức & Cẩm nang - Jet Charter Flights')
@section('meta_description', 'Đọc tin tức hàng không, mẹo đặt vé và cẩm nang du lịch từ Jet Charter Flights.')

@section('styles')
@vite(['resources/css/news.css'])
@endsection

@section('content')
<section class="bf-news-hero">
    <div class="bf-container">
        <span class="bf-eyebrow bf-eyebrow--light">Tin tức & cẩm nang</span>
        <h1>Ý tưởng cho hành trình tiếp theo</h1>
        <p>Cập nhật thông tin và khám phá những gợi ý hữu ích trước mỗi chuyến đi.</p>
    </div>
</section>

<section class="bf-section bf-news-listing">
    <div class="bf-container">
        <form class="bf-news-search" method="GET" action="{{ route('news.index') }}" role="search">
            <label class="bf-sr-only" for="news-search">Tìm bài viết</label>
            <div class="input-group">
                <span class="input-group-text bg-white"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input id="news-search" class="form-control" type="search" name="search" value="{{ $search }}" placeholder="Tìm theo tiêu đề hoặc nội dung">
                @if ($category)
                    <input type="hidden" name="category" value="{{ $category }}">
                @endif
                <button class="btn btn-primary" type="submit">Tìm kiếm</button>
            </div>
        </form>

        <nav class="bf-news-chips" aria-label="Chuyên mục tin tức">
            <a class="bf-news-chip {{ empty($category) ? 'is-active' : '' }}" href="{{ route('news.index', $search !== '' ? ['search' => $search] : []) }}" @if (empty($category)) aria-current="page" @endif>
                Tất cả <span>{{ array_sum($categoryCounts->all()) }}</span>
            </a>
            @foreach ($categories as $key => $label)
                <a class="bf-news-chip {{ $category === $key ? 'is-active' : '' }}" href="{{ route('news.index', array_filter(['category' => $key, 'search' => $search ?: null])) }}" @if ($category === $key) aria-current="page" @endif>
                    {{ $label }} <span>{{ $categoryCounts[$key] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        <div class="bf-news-layout">
            <div class="bf-news-results">
                @forelse ($articles as $article)
                    <a class="bf-news-list-card" href="{{ route('news.show', $article->slug) }}">
                        <span class="bf-news-list-card__image"><img src="{{ $article->thumbnail_url }}" alt="{{ $article->title }}" loading="lazy"></span>
                        <span class="bf-news-list-card__body">
                            <span class="bf-news-category">{{ $article->category_label }}</span>
                            <strong>{{ $article->title }}</strong>
                            <span class="bf-news-list-card__excerpt">{{ $article->excerpt }}</span>
                            <span class="bf-news-meta"><span>{{ $article->published_at?->format('d/m/Y') }}</span><span><i class="bi bi-eye" aria-hidden="true"></i> {{ number_format($article->view_count) }} lượt xem</span></span>
                        </span>
                    </a>
                @empty
                    <div class="bf-news-empty">
                        <i class="bi bi-journal-text" aria-hidden="true"></i>
                        <h2>{{ $search !== '' || $category ? 'Không có bài viết phù hợp' : 'Chưa có bài viết nào' }}</h2>
                        @if ($search !== '' || $category)
                            <p>Thử thay đổi từ khóa hoặc chọn một chuyên mục khác.</p>
                            <a class="btn btn-outline-primary" href="{{ route('news.index') }}">Xóa bộ lọc</a>
                        @endif
                    </div>
                @endforelse

                @if ($articles->hasPages())
                    <div class="bf-news-pagination">{{ $articles->links('pagination::bootstrap-5') }}</div>
                @endif
            </div>

            @if ($popular->isNotEmpty())
                <aside class="bf-news-popular" aria-labelledby="popular-news-title">
                    <h2 id="popular-news-title">Bài xem nhiều</h2>
                    <ol>
                        @foreach ($popular as $article)
                            <li>
                                <a href="{{ route('news.show', $article->slug) }}">
                                    <span>{{ $article->category_label }}</span>
                                    <strong>{{ $article->title }}</strong>
                                    <small>{{ number_format($article->view_count) }} lượt xem</small>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                </aside>
            @endif
        </div>
    </div>
</section>
@endsection
