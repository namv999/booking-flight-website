@extends('layouts.admin')

@section('title', 'Chỉnh sửa bài viết - Jet Charter Flights')

@section('content')
@vite(['resources/css/news.css'])
<div class="container-fluid px-0 bf-article-editor">
    <div class="mb-4"><span class="bf-eyebrow">Nội dung website</span><h1 class="h3 fw-bold text-dark mb-1">Chỉnh sửa bài viết</h1><p class="text-muted small mb-0">Cập nhật nội dung hoặc trạng thái xuất bản.</p></div>
    @include('admin.articles._form')
</div>
@endsection

@section('scripts')
@vite(['resources/js/news.js'])
@endsection
