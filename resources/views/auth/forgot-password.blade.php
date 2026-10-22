@extends('layouts.app')

@section('title', 'Quên mật khẩu - Jet Charter Flights')

@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<section class="bf-auth bf-auth--compact">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual"><img src="{{ asset('images/destination-da-nang.jpg') }}" alt="Bờ biển Đà Nẵng"><span class="bf-auth__shade"></span><div><span class="bf-eyebrow bf-eyebrow--light">Luôn liền mạch</span><h1>Đừng để một mật khẩu làm gián đoạn hành trình.</h1></div></aside>
        <div class="bf-auth__card">
            <a class="bf-back-link" href="{{ route('login') }}">← Quay lại đăng nhập</a>
            <div class="bf-auth__heading"><span class="bf-eyebrow">Khôi phục tài khoản</span><h2>Quên mật khẩu?</h2><p>Nhập email đã đăng ký. Chúng tôi sẽ gửi liên kết giúp bạn tạo mật khẩu mới.</p></div>
            <x-auth-session-status class="bf-alert bf-alert--success" :status="session('status')" />
            <form class="bf-form" id="forgot-password-form" method="POST" action="{{ route('password.email') }}">
                @csrf
                <label class="bf-field"><span>Email</span><input id="forgot-password-email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@example.com" required autofocus><x-input-error :messages="$errors->get('email')" class="bf-field__error" /></label>
                <button class="bf-button bf-button--primary bf-button--block" type="submit">Gửi liên kết khôi phục</button>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/auth.js'])
@endsection
