{{-- Layout tương thích cho các component Breeze cũ; các trang auth mới kế thừa layouts.app. --}}
@extends('layouts.app')

@section('title', 'Tài khoản - Jet Charter Flights')

@section('content')
    <section class="bf-auth">
        <div class="bf-container bf-auth__shell">
            <div class="bf-auth__card">
                {{ $slot }}
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script src="{{ asset('js/auth.js') }}"></script>
@endsection
