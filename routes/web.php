<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PermintaanController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Internal\DashboardController;
use App\Http\Controllers\Internal\PermintaanController as InternalPermintaanController;
use App\Http\Controllers\Internal\KatalogController as InternalKatalogController;
use App\Http\Controllers\Internal\PenggunaController;
use App\Http\Controllers\Internal\LaporanController;

// Public
Route::get('/', [PublicController::class, 'index'])->name('beranda');
Route::get('/katalog', [PublicController::class, 'katalog'])->name('katalog.index');
Route::get('/katalog/{dataset}', [PublicController::class, 'detailDataset'])->name('katalog.detail');
Route::get('/katalog/{dataset}/unduh', [PublicController::class, 'unduhDataset'])->name('katalog.unduh');
Route::get('/cek-status', [PublicController::class, 'cekStatus'])->name('cek-status');
Route::post('/cek-status', [PublicController::class, 'cekStatusPost'])->name('cek-status.post');

// OTP
Route::get('/otp', [OtpController::class, 'showForm'])->name('otp.form');
Route::post('/otp/kirim', [OtpController::class, 'kirimOtp'])->name('otp.kirim');
Route::post('/otp/kirim-ulang', [OtpController::class, 'kirimUlang'])->name('otp.kirim-ulang');
Route::post('/otp/verifikasi', [OtpController::class, 'verifikasiOtp'])->name('otp.verifikasi');

// Permintaan Data (butuh OTP)
Route::middleware(['pemohon.otp'])->group(function () {
    Route::get('/permintaan/create', [PermintaanController::class, 'create'])->name('permintaan.create');
    Route::post('/permintaan', [PermintaanController::class, 'store'])->name('permintaan.store');
    Route::get('/permintaan/{permintaan}/selesai', [PermintaanController::class, 'selesai'])->name('permintaan.selesai');
    Route::get('/permintaan/{permintaan}/unduh', [PermintaanController::class, 'unduhHasil'])->name('permintaan.unduh');
});

// Status tracking (tanpa OTP, verifikasi tiket + no HP)
Route::get('/status/{nomorTiket}', [StatusController::class, 'cek'])->name('status.cek')->where('nomorTiket', '.*');

// Auth Internal
Route::get('/internal/login', [LoginController::class, 'showLoginForm'])->name('internal.login');
Route::post('/internal/login', [LoginController::class, 'login'])->name('internal.login.post');
Route::post('/internal/logout', [LoginController::class, 'logout'])->name('internal.logout');

// Internal (butuh auth)
Route::prefix('internal')->middleware(['auth'])->name('internal.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Permintaan
    Route::get('/permintaan', [InternalPermintaanController::class, 'index'])->name('permintaan.index');
    Route::get('/permintaan/{permintaan}', [InternalPermintaanController::class, 'show'])->name('permintaan.show');
    Route::post('/permintaan/{permintaan}/keputusan', [InternalPermintaanController::class, 'keputusan'])->name('permintaan.keputusan');
    Route::post('/permintaan/{permintaan}/upload', [InternalPermintaanController::class, 'storeUploadHasil'])->name('permintaan.upload');
    Route::get('/permintaan/{permintaan}/unduh', [InternalPermintaanController::class, 'unduhHasil'])->name('permintaan.unduh');

    // Katalog (staf & admin)
    Route::middleware('permission:upload-dataset')->group(function () {
        Route::get('/katalog', [InternalKatalogController::class, 'index'])->name('katalog.index');
        Route::get('/katalog/create', [InternalKatalogController::class, 'create'])->name('katalog.create');
        Route::post('/katalog', [InternalKatalogController::class, 'store'])->name('katalog.store');
        Route::get('/katalog/{dataset}/edit', [InternalKatalogController::class, 'edit'])->name('katalog.edit');
        Route::put('/katalog/{dataset}', [InternalKatalogController::class, 'update'])->name('katalog.update');
        Route::post('/katalog/{dataset}/revisi', [InternalKatalogController::class, 'revisi'])->name('katalog.revisi');
        Route::delete('/katalog/{dataset}', [InternalKatalogController::class, 'destroy'])->name('katalog.destroy');
    });

    // Laporan (kasi/kabid/admin)
    Route::middleware('permission:lihat-laporan')->group(function () {
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan');
    });

    // Users (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::get('/pengguna/create', [PenggunaController::class, 'create'])->name('pengguna.create');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::get('/pengguna/{user}/edit', [PenggunaController::class, 'edit'])->name('pengguna.edit');
        Route::put('/pengguna/{user}', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna/{user}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');
    });
});
