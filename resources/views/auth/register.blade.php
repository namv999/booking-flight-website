@extends('layouts.app')

@section('title', 'Tạo tài khoản - Jet Charter Flights')

@section('content')
<section class="bf-auth">
    <div class="bf-container bf-auth__shell">
        <aside class="bf-auth__visual">
            <img src="{{ asset('images/destination-tokyo.jpg') }}" alt="Tokyo nhìn từ trên cao">
            <span class="bf-auth__shade"></span>
            <div><span class="bf-eyebrow bf-eyebrow--light">Thành viên Jet Explorer</span><h1>Thêm đặc quyền cho mỗi lần cất cánh.</h1><p>Lưu hành khách, theo dõi chuyến đi và nhận ưu đãi dành riêng cho bạn.</p></div>
        </aside>
        <div class="bf-auth__card">
            <div class="bf-auth__heading"><span class="bf-eyebrow">Bắt đầu ngay</span><h2>Tạo tài khoản</h2><p>Chỉ mất một phút để tham gia.</p></div>
            <form class="bf-form" id="register-form" method="POST" action="{{ route('register') }}">
                @csrf
                <label class="bf-field"><span>Họ và tên</span><input id="register-name" type="text" name="name" value="{{ old('name') }}" placeholder="Nguyễn Minh Anh" required autofocus autocomplete="name"><x-input-error :messages="$errors->get('name')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Email</span><input id="register-email" type="email" name="email" value="{{ old('email') }}" placeholder="ban@example.com" required autocomplete="username"><x-input-error :messages="$errors->get('email')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Mật khẩu</span><span class="bf-password-field"><input id="register-password" type="password" name="password" placeholder="Tối thiểu 8 ký tự" required autocomplete="new-password"><button type="button" data-password-toggle="register-password" aria-label="Hiện mật khẩu">Hiện</button></span><x-input-error :messages="$errors->get('password')" class="bf-field__error" /></label>
                <label class="bf-field"><span>Xác nhận mật khẩu</span><input id="register-password-confirmation" type="password" name="password_confirmation" placeholder="Nhập lại mật khẩu" required autocomplete="new-password"><x-input-error :messages="$errors->get('password_confirmation')" class="bf-field__error" /></label>
                <label class="bf-checkbox"><input id="accept-terms" type="checkbox" required><span>Tôi đồng ý với <a href="#">điều khoản sử dụng</a> và chính sách bảo mật.</span></label>
                <button class="bf-button bf-button--primary bf-button--block" type="submit">Tạo tài khoản</button>
            </form>
            <p class="bf-auth__switch">Đã là thành viên? <a href="{{ route('login') }}">Đăng nhập</a></p>
        </div>
    </div>
</section>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endsection
