<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\SeatSelectionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
});

// ROUTE MODULE LUỒNG ĐẶT VÉ - TÌM KIẾM CHUYẾN BAY
Route::get('/flights/search', [FlightSearchController::class, 'form'])->name('flights.search.form');
Route::get('/flights/results', [FlightSearchController::class, 'results'])->name('flights.search.results');

Route::middleware('auth')->group(function () {
    Route::post('/booking/hold', [SeatSelectionController::class, 'hold'])->name('booking.hold');
});

require __DIR__.'/auth.php';
