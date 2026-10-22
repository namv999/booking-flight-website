@extends('layouts.admin')

@section('title', 'Thêm gói hành lý')

@section('content')
    <h1>Thêm gói hành lý</h1>

    <form method="POST" action="{{ route('admin.baggage-addons.store') }}">
        @include('admin.baggage-addons._form')
    </form>
@endsection
