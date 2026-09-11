@extends('layouts.admin')

@section('title', 'Tổng quan quản trị - Jet Charter Flights')

@section('content')
    @include('dashboard.admin')
@endsection

@section('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endsection
