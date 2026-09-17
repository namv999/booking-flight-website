<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\AircraftController;
use App\Http\Controllers\Admin\AirportController;
use App\Http\Controllers\Admin\FareClassController;
use App\Http\Controllers\Admin\FlightController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\FlightSearchController;
use App\Http\Controllers\SeatSelectionController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentController;

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

// Nhóm Route Quản trị Admin - khôi phục middleware 'admin'
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('airlines', AirlineController::class);
    Route::resource('aircrafts', AircraftController::class);
    Route::resource('airports', AirportController::class);
    Route::resource('fare-classes', FareClassController::class);
    Route::resource('flights', FlightController::class);
});

// ROUTE MODULE LUỒNG ĐẶT VÉ - TÌM KIẾM CHUYẾN BAY
Route::get('/flights/search', [FlightSearchController::class, 'form'])->name('flights.search.form');
Route::get('/flights/results', [FlightSearchController::class, 'results'])->name('flights.search.results');

Route::middleware('auth')->group(function () {
    Route::post('/booking/hold', [SeatSelectionController::class, 'hold'])->name('booking.hold');
    Route::get('/booking/passengers', [BookingController::class, 'create'])->name('booking.passengers.form');
    Route::post('/booking/passengers', [BookingController::class, 'store'])->name('booking.passengers.store');
    Route::get('/payment/{booking}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{booking}', [PaymentController::class, 'store'])->name('payment.store');
});

require __DIR__.'/auth.php';