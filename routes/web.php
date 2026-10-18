<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HumanChallengeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\KtmVerificationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RoleSwitchController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Public routes
// Tantangan verifikasi "saya bukan robot" (dibatasi 30 permintaan/menit per IP)
Route::get('/human/challenge', [HumanChallengeController::class, 'show'])
    ->middleware('throttle:30,1')
    ->name('human.challenge');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/items', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show')->where('item', '^(?!create$)[A-Za-z0-9\-]+$');
Route::get('/user/{user}', [ProfileController::class, 'showUser'])->name('user.profile');

// Guest only routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Verifikasi KTM/NIM (semua akun non-admin)
    Route::get('/verifikasi-ktm', [KtmVerificationController::class, 'show'])->name('verification.ktm');
    Route::post('/verifikasi-ktm', [KtmVerificationController::class, 'upload'])->name('verification.ktm.upload');
    Route::get('/ktm/{user}', [KtmVerificationController::class, 'image'])->name('ktm.image')->where('user', '[0-9]+');

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Berkas privat (otorisasi dicek di controller)
    Route::get('/payments/{booking}/proof', [PaymentController::class, 'proof'])->name('payments.proof')->where('booking', '[0-9]+');
    Route::get('/complaints/{complaint}/evidence', [ComplaintController::class, 'evidence'])->name('complaints.evidence')->where('complaint', '[0-9]+');

    // Profil (peminjam & pemilik)
    Route::middleware('role:peminjam,pemilik')->group(function () {
        // Pusat pengaturan akun (profil, verifikasi KTM, rekening, keluar)
        Route::view('/akun', 'account.index')->name('account');

        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Bersama peminjam & pemilik: riwayat/detail transaksi dan keluhan
    Route::middleware('role:peminjam,pemilik')->group(function () {
        // Dashboard: tampilannya mengikuti mode aktif (peminjam / pemilik)
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show')->where('booking', '[0-9]+');
        Route::post('/bookings/check-availability', [BookingController::class, 'checkAvailability'])->name('bookings.check-availability');

        Route::get('/complaints/create', [ComplaintController::class, 'create'])->name('complaints.create');
        Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaints.store');

        // Satu akun, dua peran: beralih antara peminjam dan pemilik
        Route::post('/switch-role', [RoleSwitchController::class, 'switch'])->name('role.switch');

        // Ulasan dua arah (peminjam menilai pemilik dan sebaliknya)
        Route::post('/reviews/{booking}', [ReviewController::class, 'store'])->middleware('ktm')->name('reviews.store');
    });

    // ===== Aktor: PEMINJAM =====
    Route::middleware(['role:peminjam', 'ktm'])->group(function () {
        Route::get('/bookings/create/{item}', [BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    });
    Route::post('/payments/{booking}/upload', [PaymentController::class, 'upload'])->middleware('role:peminjam')->name('payments.upload');

    // ===== Aktor: PEMILIK BARANG =====
    Route::middleware('role:pemilik')->group(function () {
        Route::get('/payments', [PaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{booking}', [PaymentController::class, 'show'])->name('payments.show');
        Route::post('/payments/{booking}/accept', [PaymentController::class, 'accept'])->name('payments.accept');
        Route::post('/payments/{booking}/reject', [PaymentController::class, 'reject'])->name('payments.reject');
        Route::post('/payments/{booking}/cash', [PaymentController::class, 'cash'])->name('payments.cash');

        // Kelola barang & tanggapi booking (butuh email terverifikasi)
        Route::middleware('ktm')->group(function () {
            Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
            Route::post('/items', [ItemController::class, 'store'])->name('items.store');
            Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
            Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
            Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
            Route::patch('/items/{item}/toggle', [ItemController::class, 'toggleAvailability'])->name('items.toggle');

            Route::patch('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
            Route::patch('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
            Route::patch('/bookings/{booking}/activate', [BookingController::class, 'activate'])->name('bookings.activate');
            Route::patch('/bookings/{booking}/complete', [BookingController::class, 'complete'])->name('bookings.complete');
        });
    });

    // ===== Aktor: ADMIN =====
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('admin.moderation');
        Route::post('/complaints/{complaint}', [ModerationController::class, 'updateComplaint'])->name('admin.complaints.update');
        Route::post('/reviews/{review}/toggle', [ModerationController::class, 'toggleReview'])->name('admin.reviews.toggle');

        // Verifikasi KTM/NIM
        Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('admin.verifications');
        Route::post('/verifications/{user}/approve', [AdminVerificationController::class, 'approve'])->name('admin.verifications.approve');
        Route::post('/verifications/{user}/reject', [AdminVerificationController::class, 'reject'])->name('admin.verifications.reject');

        // Pengguna: teguran & suspend
        Route::get('/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::post('/users/{user}/warn', [AdminUserController::class, 'warn'])->name('admin.users.warn');
        Route::post('/users/{user}/suspend', [AdminUserController::class, 'suspend'])->name('admin.users.suspend');
        Route::post('/users/{user}/unsuspend', [AdminUserController::class, 'unsuspend'])->name('admin.users.unsuspend');

        // Kategori barang
        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
        Route::put('/categories/{id}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

        // Laporan transaksi
        Route::get('/reports', [AdminReportController::class, 'index'])->name('admin.reports');
        Route::get('/reports/export', [AdminReportController::class, 'export'])->name('admin.reports.export');
    });
});