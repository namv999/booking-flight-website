{{-- resources/views/dashboard/index.blade.php --}}
{{-- WRAPPER route 'dashboard' — chỉ include dashboard.user, KHÔNG chứa markup trực tiếp --}}

@extends('layouts.app')

@section('title', 'Chuyến đi của tôi - Jet Charter Flights')

@section('styles')
    @vite(['resources/css/account.css'])
@endsection

@section('content')
    @include('dashboard.user')
@endsection

@section('scripts')
    @vite(['resources/js/dashboard.js'])
@endsection
