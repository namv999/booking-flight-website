@extends('layouts.app')

@section('title', 'Thêm hồ sơ hành khách')

@section('content')
    <h1>Thêm hồ sơ hành khách</h1>

    <form method="POST" action="{{ route('saved-passengers.store') }}">
        @include('saved-passengers._form')
    </form>
@endsection
