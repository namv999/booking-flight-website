@extends('layouts.app')

@section('title', 'Jet Charter Flights - Đặt vé máy bay')

@section('styles')
    @vite(['resources/css/home.css'])
@endsection

@section('content')
    @include('home')
@endsection

@section('scripts')
    @vite(['resources/js/home.js'])
@endsection
