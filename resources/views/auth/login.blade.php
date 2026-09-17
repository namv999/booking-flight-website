@extends('layouts.app')

@section('title', 'Đăng nhập - Jet Charter Flights')

@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<section class="bf-auth">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual">
            <img src="{{ asset('images/hero-banner.jpg') }}" alt="Máy bay Jet Charter trên bầu trời">
            <span class="bf-auth__shade"></span>
            <div><span class="bf-eyebrow bf-eyebrow--light">Chào mừng trở lại</span><h1>Mỗi hành trình đẹp bắt đầu từ một lựa chọn dễ dàng.</h1><p>Quản lý lịch bay, ưu đãi và thông tin hành khách ở cùng một nơi.</p></div>
        </aside>
        <div class="bf-auth__card">
            <div class="bf-auth__heading"><span class="bf-eyebrow">Tài khoản Jet Charter</span><h2>Đăng nhập</h2><p>Tiếp tục hành trình của bạn.</p></div>
            <x-auth-session-status class="bf-alert bf-alert--success" :status="session('status')" />
            <form class="bf-form" id="login-form" method="POST" action="{{ route('login') }}">
                @csrf
                <label class="bf-field"><span>Email</span><input id="login-email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@example.com" required autofocus autocomplete="username"><x-input-error :messages="$errors->get('email')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Mật khẩu</span><span class="bf-password-field"><input id="login-password" type="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password"><button type="button" data-password-toggle="login-password" aria-label="Hiện mật khẩu">Hiện</button></span><x-input-error :messages="$errors->get('password')" class="bf-field__error" /></label>
                <div class="bf-form__meta"><label class="bf-checkbox"><input id="remember-me" type="checkbox" name="remember"><span>Ghi nhớ đăng nhập</span></label>@if (Route::has('password.request'))<a href="{{ route('password.request') }}">Quên mật khẩu?</a>@endif</div>
                <button class="bf-button bf-button--primary bf-button--block" type="submit">Đăng nhập</button>
            </form>
            <p class="bf-auth__switch">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký miễn phí</a></p>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/auth.js'])
@endsection
