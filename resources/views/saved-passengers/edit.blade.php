@extends('layouts.app')

@section('title', 'Sửa hồ sơ hành khách')

@section('content')
    <h1>Sửa hồ sơ hành khách</h1>

    <form method="POST" action="{{ route('saved-passengers.update', $savedPassenger) }}">
        @include('saved-passengers._form')
    </form>
@endsection
