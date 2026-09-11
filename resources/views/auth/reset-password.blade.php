@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu - Jet Charter Flights')

@section('content')
<section class="bf-auth bf-auth--compact">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual"><img src="{{ asset('images/destination-bangkok.jpg') }}" alt="Bangkok về đêm"><span class="bf-auth__shade"></span><div><span class="bf-eyebrow bf-eyebrow--light">Bảo mật hành trình</span><h1>Một khởi đầu mới, an toàn hơn.</h1></div></aside>
        <div class="bf-auth__card">
            <div class="bf-auth__heading"><span class="bf-eyebrow">Tài khoản của bạn</span><h2>Tạo mật khẩu mới</h2><p>Sử dụng ít nhất 8 ký tự và tránh mật khẩu bạn đã dùng trước đây.</p></div>
            <form class="bf-form" id="reset-password-form" method="POST" action="{{ route('password.store') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $request->route('token') }}">
                <label class="bf-field"><span>Email</span><input id="reset-email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Mật khẩu mới</span><span class="bf-password-field"><input id="reset-password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required autocomplete="new-password"><button type="button" data-password-toggle="reset-password" aria-label="Hiện mật khẩu">Hiện</button></span><x-input-error :messages="$errors->get('password')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Xác nhận mật khẩu</span><input id="reset-password-confirmation" type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu" required autocomplete="new-password"><x-input-error :messages="$errors->get('password_confirmation')" class="bf-field__error" /></label>
                <button class="bf-button bf-button--primary bf-button--block" type="submit">Đặt lại mật khẩu</button>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endsection
