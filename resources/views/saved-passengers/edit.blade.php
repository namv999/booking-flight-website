@extends('layouts.app')

@section('title', 'Sửa hồ sơ hành khách')
@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
<div class="bf-container py-4">
    <h1 class="h3 mb-3">Sửa hồ sơ hành khách</h1>

    <form method="POST" action="{{ route('saved-passengers.update', $savedPassenger) }}">
        @include('saved-passengers._form')
    </form>
</div>
@endsection