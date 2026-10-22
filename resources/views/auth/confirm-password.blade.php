@extends('layouts.app')

@section('title', 'Xác nhận mật khẩu - Jet Charter Flights')

@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<section class="bf-auth bf-auth--compact">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual"><img src="{{ asset('images/hero-banner.jpg') }}" alt="Máy bay trên bầu trời"><span class="bf-auth__shade"></span><div><span class="bf-eyebrow bf-eyebrow--light">Khu vực bảo mật</span><h1>Xác nhận trước khi tiếp tục.</h1></div></aside>
        <div class="bf-auth__card">
            <div class="bf-auth__heading"><span class="bf-eyebrow">Bước bảo mật</span><h2>Xác nhận mật khẩu</h2><p>Vui lòng nhập lại mật khẩu để tiếp tục thao tác này.</p></div>
            <form class="bf-form" id="confirm-password-form" method="POST" action="{{ route('password.confirm') }}">
                @csrf
                <label class="bf-field"><span>Mật khẩu</span><span class="bf-password-field"><input id="confirm-password" type="password" name="password" required autocomplete="current-password"><button type="button" data-password-toggle="confirm-password" aria-label="Hiện mật khẩu">Hiện</button></span><x-input-error :messages="$errors->get('password')" class="bf-field__error" /></label>
                <button class="bf-button bf-button--primary bf-button--block" type="submit">Xác nhận và tiếp tục</button>
            </form>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    @vite(['resources/js/auth.js'])
@endsection
