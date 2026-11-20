@extends('layouts.app')

@section('title', $article->title.' - Tin tức Jet Charter')
@section('meta_description', $article->excerpt)

@section('styles')
@vite(['resources/css/news.css'])
@endsection

@section('content')
<article class="bf-news-article">
    <div class="bf-container bf-news-article__container">
        <nav class="bf-news-breadcrumb" aria-label="Điều hướng breadcrumb">
            <a href="{{ route('home') }}">Trang chủ</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('news.index') }}">Tin tức</a><i class="bi bi-chevron-right" aria-hidden="true"></i>
            <a href="{{ route('news.index', ['category' => $article->category]) }}">{{ $article->category_label }}</a>
        </nav>

        <header class="bf-news-article__header">
            <span class="bf-news-category">{{ $article->category_label }}</span>
            <h1>{{ $article->title }}</h1>
            <p class="bf-news-article__excerpt">{{ $article->excerpt }}</p>
            <div class="bf-news-meta">
                <span>{{ $article->published_at?->format('d/m/Y') }}</span>
                @if ($article->author)
                    <span>{{ $article->author->name }}</span>
                @endif
                <span><i class="bi bi-eye" aria-hidden="true"></i> {{ number_format($article->view_count) }} lượt xem</span>
            </div>
        </header>

        <img class="bf-news-article__cover" src="{{ $article->thumbnail_url }}" alt="{{ $article->title }}">
        <div class="bf-prose">{!! $article->content_html !!}</div>

        @if ($related->isNotEmpty())
            <section class="bf-news-related" aria-labelledby="related-news-title">
                <div class="bf-section-heading">
                    <div><span class="bf-eyebrow">Cùng chuyên mục</span><h2 id="related-news-title">Bài viết liên quan</h2></div>
                </div>
                <div class="bf-news-related__grid">
                    @foreach ($related as $relatedArticle)
                        <a class="bf-news-list-card" href="{{ route('news.show', $relatedArticle->slug) }}">
                            <span class="bf-news-list-card__image"><img src="{{ $relatedArticle->thumbnail_url }}" alt="{{ $relatedArticle->title }}" loading="lazy"></span>
                            <span class="bf-news-list-card__body"><span class="bf-news-category">{{ $relatedArticle->category_label }}</span><strong>{{ $relatedArticle->title }}</strong><span class="bf-news-list-card__excerpt">{{ $relatedArticle->excerpt }}</span></span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <a class="btn btn-outline-primary bf-news-back" href="{{ route('news.index') }}"><i class="bi bi-arrow-left me-2" aria-hidden="true"></i>Quay lại tin tức</a>
    </div>
</article>
@endsection
