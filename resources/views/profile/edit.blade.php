@extends('layouts.app')

@section('title', 'Hồ sơ cá nhân - Jet Charter Flights')

@section('content')
<section class="bf-page-hero bf-page-hero--profile">
    <div class="bf-container bf-page-hero__inner">
        <div><span class="bf-eyebrow bf-eyebrow--light">Tài khoản cá nhân</span><h1>Thông tin của bạn</h1><p>Cập nhật hồ sơ và cài đặt bảo mật cho những hành trình tiếp theo.</p></div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="bf-button bf-button--light" type="submit">Đăng xuất</button></form>
    </div>
</section>
<section class="bf-profile bf-container">
    <aside class="bf-profile-nav" aria-label="Điều hướng hồ sơ">
        <a class="bf-profile-nav__link--active" href="#profile-information">Thông tin cá nhân</a>
        <a href="#profile-password">Mật khẩu</a>
        <a href="#profile-danger">Quản lý tài khoản</a>
        <a href="{{ route('dashboard') }}">Quay lại chuyến đi</a>
    </aside>
    <div class="bf-profile__content">
        <div class="bf-profile-card" id="profile-information">@include('profile.partials.update-profile-information-form')</div>
        <div class="bf-profile-card" id="profile-password">@include('profile.partials.update-password-form')</div>
        <div class="bf-profile-card bf-profile-card--danger" id="profile-danger">@include('profile.partials.delete-user-form')</div>
    </div>
</section>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endsection
