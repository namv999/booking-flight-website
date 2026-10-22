@extends('layouts.admin')

@section('title', 'Sửa gói hành lý')

@section('content')
    <h1>Sửa gói hành lý</h1>

    <form method="POST" action="{{ route('admin.baggage-addons.update', $baggageAddon) }}">
        @include('admin.baggage-addons._form')
    </form>
@endsection
