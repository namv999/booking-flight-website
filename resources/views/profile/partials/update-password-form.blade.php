<section>
    <header class="bf-form-section-heading"><span class="bf-eyebrow">Bảo mật</span><h2>Thay đổi mật khẩu</h2><p>Sử dụng mật khẩu dài và khác với các tài khoản khác của bạn.</p></header>
    <form class="bf-form" method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')
        <label class="bf-field"><span>Mật khẩu hiện tại</span><span class="bf-password-field"><input id="update-password-current" name="current_password" type="password" autocomplete="current-password"><button type="button" data-password-toggle="update-password-current" aria-label="Hiện mật khẩu">Hiện</button></span><x-input-error :messages="$errors->updatePassword->get('current_password')" class="bf-field__error" /></label>
        <div class="bf-form-grid">
            <label class="bf-field"><span>Mật khẩu mới</span><input id="update-password-new" name="password" type="password" autocomplete="new-password" placeholder="Tối thiểu 8 ký tự"><x-input-error :messages="$errors->updatePassword->get('password')" class="bf-field__error" /></label>
            <label class="bf-field"><span>Xác nhận mật khẩu</span><input id="update-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="Nhập lại mật khẩu"><x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="bf-field__error" /></label>
        </div>
        <div class="bf-form__actions"><button class="bf-button bf-button--primary" type="submit">Cập nhật mật khẩu</button>@if (session('status') === 'password-updated')<span class="bf-save-status" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)">Đã lưu</span>@endif</div>
    </form>
</section>
