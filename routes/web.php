<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SavedPassengerController;
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
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\BookingHistoryController;

// Route::get('/', function () {
//     return view('home');
// });
Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/dashboard', function () {
    if (Auth::user()->role === 'admin') {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('saved-passengers', SavedPassengerController::class)->except('show');
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
Route::get('/flights/results', [FlightSearchController::class, 'results'])->name('flights.search.results');

Route::middleware('auth')->group(function () {
    Route::post('/booking/precheck', [SeatSelectionController::class, 'precheck'])->name('booking.precheck');
    Route::get('/booking/passengers', [BookingController::class, 'create'])->name('booking.passengers.form');
    Route::post('/booking/passengers', [BookingController::class, 'store'])->name('booking.passengers.store');
    Route::get('/payment/{booking}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{booking}', [PaymentController::class, 'store'])->name('payment.store');
    Route::get('/booking-history', [BookingHistoryController::class, 'index'])->name('booking-history.index');
    Route::get('/booking-history/{id}', [BookingHistoryController::class, 'show'])->name('booking-history.show');
});

require __DIR__.'/auth.php';
