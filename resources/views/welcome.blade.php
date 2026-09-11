@extends('layouts.app')

@section('title', 'Jet Charter Flights - Đặt vé máy bay')

@section('content')
    @include('home')
@endsection

@section('scripts')
    <script src="{{ asset('js/home.js') }}"></script>
@endsection
