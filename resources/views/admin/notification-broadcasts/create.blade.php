@extends('layouts.admin')

@section('title', 'Gửi thông báo - Jet Charter Flights')

@section('content')
<div class="container-fluid px-0 bf-broadcast-page" id="bf-broadcast-form-page">
    <div class="mb-4">
        <span class="bf-eyebrow">Liên lạc khách hàng</span>
        <h1 class="h3 fw-bold text-dark mb-1">Gửi thông báo chung</h1>
        <p class="text-muted small mb-0">Soạn một thông báo để gửi tới toàn bộ khách hàng.</p>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-7">
            <form method="POST" action="{{ route('admin.notification-broadcasts.store') }}" class="card border-0 shadow-sm bf-broadcast-form" id="bf-broadcast-form">
                @csrf
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label for="broadcast-type-select" class="form-label fw-semibold">Loại thông báo</label>
                        <select id="broadcast-type-select" name="type" class="form-select @error('type') is-invalid @enderror" data-bf-preview-type>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', 'promotion') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="broadcast-title" class="form-label fw-semibold">Tiêu đề</label>
                        <input id="broadcast-title" type="text" name="title" maxlength="150" required value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" data-bf-preview-title>
                        <div class="d-flex justify-content-between mt-1"><div>@error('title') <span class="text-danger small">{{ $message }}</span> @enderror</div><small class="text-muted"><span data-bf-title-count>{{ mb_strlen(old('title', '')) }}</span>/150</small></div>
                    </div>

                    <div class="mb-3">
                        <label for="broadcast-message" class="form-label fw-semibold">Nội dung</label>
                        <textarea id="broadcast-message" name="message" maxlength="500" rows="5" required class="form-control @error('message') is-invalid @enderror" data-bf-preview-message>{{ old('message') }}</textarea>
                        <div class="d-flex justify-content-between mt-1"><div>@error('message') <span class="text-danger small">{{ $message }}</span> @enderror</div><small class="text-muted"><span data-bf-message-count>{{ mb_strlen(old('message', '')) }}</span>/500</small></div>
                    </div>

                    <div class="mb-4">
                        <label for="broadcast-url" class="form-label fw-semibold">Liên kết đích <span class="text-muted fw-normal">(không bắt buộc)</span></label>
                        <input id="broadcast-url" type="text" name="url" maxlength="255" value="{{ old('url') }}" placeholder="/news/ten-bai hoặc /#search-box" class="form-control @error('url') is-invalid @enderror">
                        <div class="form-text">Chỉ nhập đường dẫn nội bộ bắt đầu bằng một dấu /. Để trống nếu không cần liên kết.</div>
                        @error('url') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>

                    <div class="bf-broadcast-warning">
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                        <div><strong>Thông báo sẽ được gửi ngay tới {{ number_format($customerCount) }} khách hàng và không thể thu hồi.</strong>
                            <label class="form-check mt-2 mb-0"><input class="form-check-input" type="checkbox" data-bf-broadcast-confirm><span class="form-check-label">Tôi đã kiểm tra nội dung và muốn gửi.</span></label>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.notification-broadcasts.index') }}" class="btn btn-outline-secondary">Quay lại</a>
                        <button type="submit" class="btn btn-primary" data-bf-broadcast-submit disabled><i class="bi bi-send me-1"></i>Gửi thông báo</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="col-12 col-xl-5">
            <section class="card border-0 shadow-sm bf-broadcast-preview-card" aria-labelledby="bf-broadcast-preview-title">
                <div class="card-header bg-white py-3"><h2 id="bf-broadcast-preview-title" class="h6 fw-bold mb-0">Xem trước</h2><small class="text-muted">Cách thông báo hiển thị trong chuông</small></div>
                <div class="card-body bg-light">
                    @php
                        $previewMeta = config('notifications.types.'.old('type', 'promotion'), config('notifications.fallback'));
                    @endphp
                    <div class="bf-notif-item bf-notif-preview-item">
                        <span class="bf-notif-item__icon {{ $previewMeta['class'] }}" data-bf-preview-icon-wrap><i class="bi {{ $previewMeta['icon'] }}" data-bf-preview-icon aria-hidden="true"></i></span>
                        <span class="bf-notif-item__copy">
                            <span class="bf-notif-item__title" data-bf-preview-title-text>{{ old('title') ?: 'Tiêu đề thông báo' }}</span>
                            <span class="bf-notif-item__message" data-bf-preview-message-text>{{ old('message') ?: 'Nội dung thông báo sẽ hiển thị tại đây.' }}</span>
                            <span class="bf-notif-item__time">Vừa xong</span>
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
