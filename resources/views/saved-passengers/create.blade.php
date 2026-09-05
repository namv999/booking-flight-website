{{-- TEMP LAYOUT: đang dùng layout tạm để test, sẽ đổi sang layouts.app khi C hoàn thành --}}
@extends('layouts.temp')

@section('title', 'Thêm hồ sơ hành khách')

@section('content')
    <h1>Thêm hồ sơ hành khách</h1>

    <form method="POST" action="{{ route('saved-passengers.store') }}">
        @include('saved-passengers._form')
    </form>
@endsection