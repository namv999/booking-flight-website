<?php
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AirlineController;
use App\Http\Controllers\Admin\AircraftController;
use App\Http\Controllers\Admin\AirportController;
use App\Http\Controllers\Admin\FareClassController;
use App\Http\Controllers\Admin\FlightController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

// Route dashboard mặc định của Laravel cho user
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Nhóm Route Quản trị Admin (Sử dụng middleware 'auth' để tránh lỗi 403 do chưa định nghĩa middleware 'admin')
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Route Dashboard thống kê Admin tại /admin/dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('airlines', AirlineController::class)->except(['show']);
    Route::resource('aircrafts', AircraftController::class);
    Route::resource('airports', AirportController::class);
    Route::resource('fare-classes', FareClassController::class);
    Route::resource('flights', FlightController::class);
});

require __DIR__.'/auth.php';