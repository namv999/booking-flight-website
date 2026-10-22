{{-- Khách gửi yêu cầu cập nhật hộ chiếu cho từng hành khách của đơn quốc tế đã thanh toán --}}
{{-- Biến: $booking (đã load passengers.passportUpdateRequests, mới nhất trước), $passportUpdateError (null = được phép gửi) --}}
@php
    $countries = config('countries');
    $statusMeta = [
        'pending' => ['bg-warning text-dark', 'bi-clock', 'Đang chờ duyệt'],
        'approved' => ['bg-success text-white', 'bi-check-circle', 'Đã duyệt'],
        'rejected' => ['bg-danger text-white', 'bi-x-circle', 'Bị từ chối'],
    ];
@endphp

<div class="bf-payment-card mb-4 bf-print-hide" id="passport-update">
    <div class="bf-payment-card__header">
        <h2 class="bf-payment-card__title">
            <i class="bi bi-person-vcard text-primary" aria-hidden="true"></i>
            <span>Cập nhật hộ chiếu</span>
        </h2>
        <span class="text-muted small">Dùng khi nhập sai hoặc vừa được cấp hộ chiếu mới</span>
    </div>

    @if (session('passport_status'))
        <div class="alert alert-success d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-check-circle-fill flex-shrink-0" aria-hidden="true"></i>
            <div>{{ session('passport_status') }}</div>
        </div>
    @endif
    @if (session('passport_error'))
        <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
            <div>{{ session('passport_error') }}</div>
        </div>
    @endif
    @if ($passportUpdateError)
        <div class="alert alert-secondary small mb-3">{{ $passportUpdateError }}</div>
    @endif

    @foreach ($booking->passengers as $passenger)
        @php
            $latest = $passenger->passportUpdateRequests->first();
            $hasPending = $latest?->isPending() ?? false;
            $canSubmit = ! $passportUpdateError && ! $hasPending;
            $formFailed = (string) old('passenger_id') === (string) $passenger->id;
            $val = fn (string $field, $current) => $formFailed ? old($field, $current) : $current;
            $err = fn (string $field) => $formFailed ? $errors->first($field) : null;
            $key = 'passport-form-'.$passenger->id;
        @endphp

        <div class="border rounded p-3 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <strong class="text-navy">{{ $passenger->full_name }}</strong>
                    <div class="small text-muted">
                        Hộ chiếu {{ $passenger->document_number ?: '-' }}
                        &bull; {{ $passenger->nationality_label ?? '-' }}
                        &bull; Hết hạn {{ $passenger->document_expiry_date?->format('d/m/Y') ?? '-' }}
                    </div>
                </div>
                @if ($canSubmit)
                    <button class="btn btn-sm btn-outline-primary" type="button"
                            data-bs-toggle="collapse" data-bs-target="#{{ $key }}"
                            aria-expanded="{{ $formFailed ? 'true' : 'false' }}" aria-controls="{{ $key }}">
                        Gửi yêu cầu cập nhật
                    </button>
                @endif
            </div>

            @if ($latest)
                @php [$badgeClass, $badgeIcon, $badgeText] = $statusMeta[$latest->status]; @endphp
                <div class="mt-2 small">
                    <span class="badge {{ $badgeClass }}"><i class="bi {{ $badgeIcon }} me-1"></i>{{ $badgeText }}</span>
                    <span class="text-muted">Gửi lúc {{ $latest->created_at?->format('H:i d/m/Y') }}</span>
                    <div class="mt-1"><strong>Lý do:</strong> {{ $latest->reason }}</div>
                    @if ($latest->admin_note)
                        <div class="mt-1"><strong>Ghi chú của quản trị viên:</strong> {{ $latest->admin_note }}</div>
                    @endif
                </div>
            @endif

            @if ($canSubmit)
                <div class="collapse {{ $formFailed ? 'show' : '' }}" id="{{ $key }}">
                    <form method="POST" class="mt-3"
                          action="{{ route('booking-history.passport-update.store', [$booking->id, $passenger->id]) }}">
                        @csrf
                        <input type="hidden" name="passenger_id" value="{{ $passenger->id }}">

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_name">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bf-input @if ($err('full_name')) is-invalid @endif" id="{{ $key }}_name"
                                       name="full_name" value="{{ $val('full_name', $passenger->full_name) }}" required>
                                @if ($err('full_name')) <div class="invalid-feedback d-block">{{ $err('full_name') }}</div> @endif
                                <div class="form-text small text-muted">Như trên hộ chiếu, viết không dấu, ví dụ NGUYEN VAN A</div>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_doc">Số hộ chiếu <span class="text-danger">*</span></label>
                                <input type="text" class="form-control bf-input @if ($err('document_number')) is-invalid @endif" id="{{ $key }}_doc"
                                       name="document_number" value="{{ $val('document_number', $passenger->document_number) }}" required>
                                @if ($err('document_number')) <div class="invalid-feedback d-block">{{ $err('document_number') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_dob">Ngày sinh <span class="text-danger">*</span></label>
                                <input type="date" class="form-control bf-input @if ($err('date_of_birth')) is-invalid @endif" id="{{ $key }}_dob"
                                       name="date_of_birth" max="{{ date('Y-m-d') }}"
                                       value="{{ $val('date_of_birth', $passenger->date_of_birth?->format('Y-m-d')) }}" required>
                                @if ($err('date_of_birth')) <div class="invalid-feedback d-block">{{ $err('date_of_birth') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold small text-navy d-block">Giới tính <span class="text-danger">*</span></label>
                                @php $gender = $val('gender', $passenger->gender); @endphp
                                <div class="d-flex align-items-center gap-3 pt-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="{{ $key }}_male" value="male" {{ $gender === 'male' ? 'checked' : '' }} required>
                                        <label class="form-check-label small" for="{{ $key }}_male">Nam</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" id="{{ $key }}_female" value="female" {{ $gender === 'female' ? 'checked' : '' }} required>
                                        <label class="form-check-label small" for="{{ $key }}_female">Nữ</label>
                                    </div>
                                </div>
                                @if ($err('gender')) <div class="invalid-feedback d-block">{{ $err('gender') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_nat">Quốc tịch <span class="text-danger">*</span></label>
                                <select class="form-select bf-input @if ($err('nationality')) is-invalid @endif" id="{{ $key }}_nat" name="nationality" required>
                                    <option value="">-- Chọn quốc gia --</option>
                                    @foreach ($countries as $code => $name)
                                        <option value="{{ $code }}" {{ $val('nationality', $passenger->nationality) === $code ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                                @if ($err('nationality')) <div class="invalid-feedback d-block">{{ $err('nationality') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_issued">Quốc gia cấp <span class="text-danger">*</span></label>
                                <select class="form-select bf-input @if ($err('document_issued_country')) is-invalid @endif" id="{{ $key }}_issued" name="document_issued_country" required>
                                    <option value="">-- Chọn quốc gia --</option>
                                    @foreach ($countries as $code => $name)
                                        <option value="{{ $code }}" {{ $val('document_issued_country', $passenger->document_issued_country) === $code ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                                    @endforeach
                                </select>
                                @if ($err('document_issued_country')) <div class="invalid-feedback d-block">{{ $err('document_issued_country') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_issue">Ngày cấp <span class="text-danger">*</span></label>
                                <input type="date" class="form-control bf-input @if ($err('document_issue_date')) is-invalid @endif" id="{{ $key }}_issue"
                                       name="document_issue_date" max="{{ date('Y-m-d') }}"
                                       value="{{ $val('document_issue_date', $passenger->document_issue_date?->format('Y-m-d')) }}" required>
                                @if ($err('document_issue_date')) <div class="invalid-feedback d-block">{{ $err('document_issue_date') }}</div> @endif
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_expiry">Ngày hết hạn <span class="text-danger">*</span></label>
                                <input type="date" class="form-control bf-input @if ($err('document_expiry_date')) is-invalid @endif" id="{{ $key }}_expiry"
                                       name="document_expiry_date"
                                       value="{{ $val('document_expiry_date', $passenger->document_expiry_date?->format('Y-m-d')) }}" required>
                                @if ($err('document_expiry_date')) <div class="invalid-feedback d-block">{{ $err('document_expiry_date') }}</div> @endif
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-navy" for="{{ $key }}_reason">Lý do cập nhật <span class="text-danger">*</span></label>
                                <textarea class="form-control bf-input @if ($err('reason')) is-invalid @endif" id="{{ $key }}_reason" name="reason" rows="2" maxlength="500"
                                          placeholder="Ví dụ: nhập sai số hộ chiếu / vừa được cấp hộ chiếu mới" required>{{ $val('reason', '') }}</textarea>
                                @if ($err('reason')) <div class="invalid-feedback d-block">{{ $err('reason') }}</div> @endif
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-sm">Gửi yêu cầu</button>
                            <span class="small text-muted ms-2">Quản trị viên sẽ xem xét; thông tin trên vé chỉ đổi khi yêu cầu được duyệt.</span>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endforeach
</div>
