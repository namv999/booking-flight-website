{{-- TEMP LAYOUT: đang dùng layout tạm để test, sẽ đổi sang layouts.admin khi Tuyết hoàn thành --}}
@extends('layouts.temp')

@section('title', 'Sửa gói hành lý')

@section('content')
    <h1>Sửa gói hành lý</h1>

    <form method="POST" action="{{ route('admin.baggage-addons.update', $baggageAddon) }}">
        @include('admin.baggage-addons._form')
    </form>
@endsection