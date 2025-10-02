<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MerchController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\AdminMerchController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\DashboardMerchController;


/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
*/

// ====================
// FRONTEND ROUTES
// ====================

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Login user (frontend)
Route::get('/loginuser', function () {
    return view('auth.loginuser');
})->name('loginuser');

// Cara Memesan
Route::get('/cara-memesan', function () {
    return view('cara-memesan');
})->name('cara.memesan');

// Band Info
Route::get('/info/{id}', [InfoController::class, 'show'])->name('info.show');

// Static Pages
Route::get('/band/negatifa', fn() => view('band.negatifa'))->name('band.negatifa');
Route::get('/about-us', fn() => view('about-us'))->name('about.us');
Route::get('/privacy-policy', fn() => view('privacy-policy'))->name('privacy.policy');
Route::get('/terms', fn() => view('terms'))->name('terms');

// ====================
// TICKET ROUTES
// ====================

// Form tiket
Route::get('/tiket', [TicketController::class, 'form'])->name('ticket.form');
Route::post('/tiket', [TicketController::class, 'store'])->name('ticket.store');

// Halaman pembayaran
Route::get('/ticket/payment/{id}', [TicketController::class, 'payment'])->name('ticket.payment');
Route::post('/ticket/pay/{id}', [TicketController::class, 'processPayment'])->name('ticket.pay');

// Batalkan transaksi
Route::post('/ticket/cancel/{id}', [TicketController::class, 'cancel'])->name('ticket.cancel');

// Status transaksi
Route::get('/tiket/success/{id}', [TicketController::class, 'success'])->name('ticket.success');
Route::get('/tiket/failed/{id}', [TicketController::class, 'failed'])->name('ticket.failed');

// Webhook Ticket
Route::get('/tickets/{id}', [WebhookController::class, 'show'])->name('tickets.show');


    // Absensi
Route::get('/absen/{kode}', [AbsensiController::class, 'showPasswordForm'])->name('absen.form');
Route::post('/absen/{kode}', [AbsensiController::class, 'handleScan'])->name('absen.submit');
Route::get('/admin/absensi', [DashboardController::class, 'absensi'])->name('admin.absensi');
Route::post('/admin/absensi/{transaction}/mark', [AdminController::class, 'markPresence'])->name('admin.absensi.mark');
Route::post('/admin/absensi/{transaction}/cancel', [AdminController::class, 'cancelPresence'])->name('admin.absensi.cancel');
Route::post('/absensi/manual/{id}', [AdminController::class, 'absenManual'])->name('admin.absensi.manual');
Route::post('/absensi/batal/{id}', [AdminController::class, 'batalAbsen'])->name('admin.absensi.batal');


// ====================
// MERCH ROUTES
// ====================

Route::get('/merchandise/{event_id}', [MerchController::class, 'index'])->name('merchandise.index');
Route::get('/merch/payment/{id}', [MerchController::class, 'processPayment'])->name('merch.payment');

Route::get('/merch', [MerchController::class, 'index'])->name('merch.index');
Route::post('/merch/checkout', [MerchController::class, 'checkout'])->name('merch.checkout');
Route::post('/merch/preview', [MerchController::class, 'preview'])->name('merch.preview');

Route::get('/merch/success/{id}', [MerchController::class, 'success'])->name('merch.success');
Route::get('/merch/failed/{id}', [MerchController::class, 'failed'])->name('merch.failed');


// Optional: kalau memang mau render view langsung
// Route::get('/merch/success/{id}', fn($id) => view('merch.success', compact('id')))->name('merch.success');
// Route::get('/merch/failed/{id}', fn($id) => view('merch.failed', compact('id')))->name('merch.failed');

// ====================
// ADMIN ROUTES
// ====================

// Login
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

// Routes with auth middleware
Route::middleware('auth')->group(function () {

    // Dashboard
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard/export-excel', [DashboardController::class, 'exportSimpleExcel'])->name('admin.dashboard.export.excel');
    Route::get('/admin/dashboard/export-pdf', [DashboardController::class, 'exportPDF'])->name('admin.dashboard.export.pdf');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Transactions
    Route::post('/admin/transactions/{id}/regenerate-qr', [DashboardController::class, 'regenerateQR'])->name('admin.transactions.regenerateQR');
    Route::post('/admin/transactions/regenerate-qr', [DashboardController::class, 'regenerateAllQR'])->name('admin.transactions.regenerate-qr');
    
    Route::get('/admin/merch/dashboard', [DashboardMerchController::class, 'index'])->name('admin.merch.dashboard');
    Route::get('/admin/merch/dashboard/export-excel', [DashboardMerchController::class, 'exportSimpleExcel'])->name('admin.merch.dashboard.export.excel');
    Route::get('/admin/merch/dashboard/export-pdf', [DashboardMerchController::class, 'exportPDF'])->name('admin.merch.dashboard.export.pdf');
    Route::post('/admin/merch/transactions/{id}/regenerate-qr', [DashboardMerchController::class, 'regenerateQR'])->name('admin.merch.transactions.regenerateQR');
    Route::post('/admin/merch/transactions/regenerate-qr', [DashboardMerchController::class, 'regenerateAllQR'])->name('admin.merch.transactions.regenerate-qr');

    // Tickets
    Route::get('/tickets/{kode}', [TicketController::class, 'show'])->name('tickets.show');

    // Guest QR
    Route::get('/guest/qr/{kode}', [GuestController::class, 'showQr'])->name('guests.qr');
    Route::get('/guest/{kode}/export-qr', [GuestController::class, 'exportGuestQR'])->name('guest.export.qr');



    // Attendee detail
    Route::get('/admin/attendee/{email}', [AdminController::class, 'showAttendeeDetail'])->name('admin.attendee.detail');

    // ====================
// ADMIN EVENT ROUTES
// ====================
    Route::get('/admin/event', [\App\Http\Controllers\AdminEventController::class, 'index'])->name('admin.event.index');
    Route::post('/admin/event', [\App\Http\Controllers\AdminEventController::class, 'store'])->name('admin.event.store');
    Route::get('/admin/event/{id}', [\App\Http\Controllers\AdminEventController::class, 'show'])->name('admin.event.show'); // detail (JSON/modal)
    Route::put('/admin/event/{id}', [\App\Http\Controllers\AdminEventController::class, 'update'])->name('admin.event.update');
    Route::delete('/admin/event/{id}', [\App\Http\Controllers\AdminEventController::class, 'destroy'])->name('admin.event.destroy');
    Route::get('/admin/event/{id}/edit', [\App\Http\Controllers\AdminEventController::class, 'edit'])->name('admin.event.edit');


    // Admin Merch
    Route::get('/admin/merch', [AdminMerchController::class, 'index'])->name('admin.merch.index');
    Route::post('/admin/merch', [AdminMerchController::class, 'store'])->name('admin.merch.store');
    Route::get('/admin/merch/{id}', [AdminMerchController::class, 'show'])->name('admin.merch.show'); // detail produk (JSON, untuk modal)
    Route::put('/admin/merch/{id}', [AdminMerchController::class, 'update'])->name('admin.merch.update');
    Route::delete('/admin/merch/{id}', [AdminMerchController::class, 'destroy'])->name('admin.merch.destroy');
    Route::get('/admin/merch/{id}/edit', [AdminMerchController::class, 'edit'])->name('admin.merch.edit');
    // Merch QR
    Route::get('/guest/merch/qr/{kode_unik}', [MerchController::class, 'showQr'])
    ->name('guests.merch.qr');


    Route::get('/qrcodes/{filename}', function ($filename) {
    $path = base_path('public_html/qrcodes/' . $filename);

    if (!file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->where('filename', '.*');



});