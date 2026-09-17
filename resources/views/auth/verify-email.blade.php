@extends('layouts.app')

@section('title', 'Xác thực email - Jet Charter Flights')

@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<section class="bf-auth bf-auth--compact">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual"><img src="{{ asset('images/destination-ha-noi.jpg') }}" alt="Hà Nội nhìn từ trên cao"><span class="bf-auth__shade"></span><div><span class="bf-eyebrow bf-eyebrow--light">Gần hoàn tất</span><h1>Xác thực email để sẵn sàng cất cánh.</h1></div></aside>
        <div class="bf-auth__card">
            <div class="bf-auth__badge" aria-hidden="true">@</div>
            <div class="bf-auth__heading"><span class="bf-eyebrow">Kiểm tra hộp thư</span><h2>Xác thực email</h2><p>Chúng tôi đã gửi một liên kết xác thực. Hãy mở email và chọn liên kết để kích hoạt tài khoản.</p></div>
            @if (session('status') == 'verification-link-sent')<div class="bf-alert bf-alert--success">Một liên kết xác thực mới đã được gửi tới email của bạn.</div>@endif
            <div class="bf-auth__actions">
                <form method="POST" action="{{ route('verification.send') }}">@csrf<button class="bf-button bf-button--primary bf-button--block" type="submit">Gửi lại email xác thực</button></form>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="bf-button bf-button--ghost bf-button--block" type="submit">Đăng xuất</button></form>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/auth.js'])
@endsection
