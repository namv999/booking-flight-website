@php
    $article = $article ?? new \App\Models\Article();
    $isEditing = $article->exists;
    $publishedValue = old('published_at', $article->published_at?->format('Y-m-d\\TH:i'));
@endphp

<form class="bf-article-form" action="{{ $isEditing ? route('admin.articles.update', $article) : route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($isEditing) @method('PUT') @endif

    @if ($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Vui lòng kiểm tra lại các trường được đánh dấu.</strong></div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm"><div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="title">Tiêu đề <span class="text-danger">*</span></label>
                    <input id="title" type="text" name="title" maxlength="200" required class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $article->title) }}" placeholder="Nhập tiêu đề bài viết">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="excerpt">Mô tả ngắn <span class="text-danger">*</span></label>
                    <textarea id="excerpt" name="excerpt" rows="3" maxlength="300" required class="form-control @error('excerpt') is-invalid @enderror" placeholder="Tóm tắt nội dung bài viết">{{ old('excerpt', $article->excerpt) }}</textarea>
                    <div class="d-flex justify-content-between"><small class="text-muted">Tối đa 300 ký tự.</small><small class="text-muted"><span data-excerpt-count>{{ mb_strlen(old('excerpt', $article->excerpt ?? '')) }}</span>/300</small></div>
                    @error('excerpt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold" for="content">Nội dung bài viết <span class="text-danger">*</span></label>
                    <textarea id="content" name="content" rows="18" required class="form-control bf-article-content @error('content') is-invalid @enderror" placeholder="Viết nội dung bằng Markdown">{{ old('content', $article->content) }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="bf-markdown-help mt-3"><strong><i class="bi bi-markdown me-1" aria-hidden="true"></i>Gợi ý Markdown</strong><span><code>## Tiêu đề</code> · <code>**đậm**</code> · <code>*nghiêng*</code> · <code>- danh sách</code> · <code>1. đánh số</code> · <code>[chữ](https://...)</code></span><small>Không hỗ trợ HTML.</small></div>
                </div>
            </div></div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Xuất bản</h2>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="status">Trạng thái <span class="text-danger">*</span></label>
                    <select id="status" name="status" required class="form-select @error('status') is-invalid @enderror">
                        <option value="draft" @selected(old('status', $article->status ?? 'draft') === 'draft')>Nháp</option>
                        <option value="published" @selected(old('status', $article->status ?? 'draft') === 'published')>Đã xuất bản</option>
                    </select>
                    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" for="published_at">Ngày xuất bản</label>
                    <input id="published_at" type="datetime-local" name="published_at" value="{{ $publishedValue }}" class="form-control @error('published_at') is-invalid @enderror">
                    <small class="form-text">Để trống để xuất bản ngay. Có thể đặt lịch trong tương lai.</small>
                    @error('published_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $article->is_featured ?? false))>
                    <label class="form-check-label fw-semibold" for="is_featured">Ghim lên trang chủ</label>
                </div>
            </div></div>

            <div class="card border-0 shadow-sm mb-4"><div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Chuyên mục</h2>
                <label class="form-label fw-semibold" for="category">Chọn chuyên mục <span class="text-danger">*</span></label>
                <select id="category" name="category" required class="form-select @error('category') is-invalid @enderror">
                    <option value="">Chọn chuyên mục</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category', $article->category) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div></div>

            <div class="card border-0 shadow-sm"><div class="card-body p-4">
                <h2 class="h6 fw-bold mb-3">Ảnh đại diện</h2>
                @if ($isEditing)
                    <img class="bf-article-preview mb-3" src="{{ $article->thumbnail_url }}" alt="Ảnh hiện tại" data-thumbnail-preview>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="remove_thumbnail" name="remove_thumbnail" value="1" @checked(old('remove_thumbnail'))><label class="form-check-label" for="remove_thumbnail">Xóa ảnh đại diện hiện tại</label></div>
                @else
                    <img class="bf-article-preview mb-3 d-none" src="" alt="Xem trước ảnh" data-thumbnail-preview>
                @endif
                <label class="form-label fw-semibold" for="thumbnail">Tải ảnh lên</label>
                <input id="thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" class="form-control @error('thumbnail') is-invalid @enderror" data-thumbnail-input>
                <small class="form-text">JPG, PNG hoặc WEBP, tối đa 2MB.</small>
                @error('thumbnail')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div></div>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1" aria-hidden="true"></i>{{ $isEditing ? 'Lưu thay đổi' : 'Tạo bài viết' }}</button>
        <a class="btn btn-outline-secondary" href="{{ route('admin.articles.index') }}">Hủy</a>
    </div>
</form>
