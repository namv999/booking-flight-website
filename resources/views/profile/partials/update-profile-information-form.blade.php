<section>
    <header class="bf-form-section-heading"><span class="bf-eyebrow">Hồ sơ</span><h2>Thông tin cá nhân</h2><p>Cập nhật họ tên và địa chỉ email dùng cho tài khoản.</p></header>
    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
    <form class="bf-form" method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')
        <div class="bf-form-grid">
            <label class="bf-field"><span>Họ và tên</span><input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name"><x-input-error class="bf-field__error" :messages="$errors->get('name')" /></label>
            <label class="bf-field"><span>Email</span><input id="profile-email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username"><x-input-error class="bf-field__error" :messages="$errors->get('email')" /></label>
        </div>
        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="bf-alert">Email của bạn chưa được xác thực. <button form="send-verification" type="submit">Gửi lại email xác thực</button></div>
            @if (session('status') === 'verification-link-sent')<div class="bf-alert bf-alert--success">Liên kết xác thực mới đã được gửi.</div>@endif
        @endif
        <div class="bf-form__actions"><button class="bf-button bf-button--primary" type="submit">Lưu thay đổi</button>@if (session('status') === 'profile-updated')<span class="bf-save-status" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)">Đã lưu</span>@endif</div>
    </form>
</section>
