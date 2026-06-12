<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Controllers
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
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\DashboardMerchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Eo\EoController;
use App\Http\Controllers\Eo\EoEventController;
use App\Http\Controllers\Eo\EoDashboardController;
use App\Http\Controllers\Eo\EoMerchController;
use App\Http\Controllers\Owner\EventApprovalController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Eo\TransactionController;
use App\Http\Controllers\Eo\SaldoController;
use App\Http\Controllers\Owner\WithdrawalApprovalController;
use App\Http\Controllers\Eo\MerchTransactionController;
use App\Http\Controllers\Owner\OwnerController;

/*
|--------------------------------------------------------------------------
| 1. PUBLIC / FRONTEND ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about-us', fn() => view('about-us'))->name('about.us');
Route::get('/privacy-policy', fn() => view('privacy-policy'))->name('privacy.policy');
Route::get('/terms', fn() => view('terms'))->name('terms');
Route::get('/cara-memesan', fn() => view('cara-memesan'))->name('cara.memesan');
Route::get('/layanan-event', fn() => view('layanan_event'));

// Info & Band
Route::get('/info/{id}', [InfoController::class, 'show'])->name('info.show');
Route::get('/band/negatifa', fn() => view('band.negatifa'))->name('band.negatifa');


/*
|--------------------------------------------------------------------------
| 2. GUEST & AUTHENTICATION ROUTES (GOOGLE)
|--------------------------------------------------------------------------
*/
// Login User Frontend
Route::get('/loginuser', fn() => view('auth.loginuser'))->name('loginuser');

// Google Auth
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Logout User
Route::post('/user/logout', function () {
    Auth::guard('user')->logout();
    return redirect('/');
})->name('user.logout');


/*
|--------------------------------------------------------------------------
| 3. PROTECTED USER ROUTES (Harus Login Akun User)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:user')->group(function () {

    // ==========================
    // PROFILE USER
    // ==========================
    Route::get('/complete-profile', [ProfileController::class, 'edit'])
        ->name('profile.complete');

    Route::post('/complete-profile', [ProfileController::class, 'update'])
        ->name('profile.complete.store');

    // ==========================
    // RIWAYAT PEMBELIAN
    // ==========================
    Route::get('/riwayat-tiket', [UserController::class, 'myTickets'])
        ->name('user.tickets');

    Route::get('/riwayat-merch', [UserController::class, 'myMerch'])
        ->name('user.merch');

    // ==========================
    // REGISTRASI EO
    // ==========================
    Route::get('/eo/register', function () {

        $userId = Auth::guard('user')->id();

        $eo = DB::table('eo')
            ->where('user_id', $userId)
            ->first();

        if (!$eo) {
            return view('eo.register');
        }

        if ($eo->status === 'pending') {
            return redirect()->route('eo.waiting');
        }

        if ($eo->status === 'approved') {
            return redirect()->route('eo.dashboard');
        }

        if ($eo->status === 'rejected') {
            return view('eo.register')
                ->with(
                    'error',
                    'Pengajuan sebelumnya ditolak, silakan daftar ulang'
                );
        }

        return view('eo.register');

    })->name('eo.register');

    Route::post('/eo/register', [EoController::class, 'store'])
        ->name('eo.store');

    Route::get('/eo/waiting', fn() => view('eo.waiting'))
        ->name('eo.waiting');

    // ==========================
    // EO AREA
    // ==========================
    Route::prefix('eo')->name('eo.')->group(function () {

        // Dashboard
        Route::get('/dashboard', [EoDashboardController::class, 'index'])
            ->name('dashboard');

        // Profile
        Route::get('/profile', [EoController::class, 'profile'])
            ->name('profile');

        Route::post('/profile', [EoController::class, 'updateProfile'])
            ->name('profile.update');

        // =================================
        // TRANSAKSI TIKET
        // =================================
        Route::get('/transactions', [TransactionController::class, 'index'])
            ->name('transactions');

        Route::get('/transactions/export-excel', [TransactionController::class, 'exportSimpleExcel'])
            ->name('transactions.export.excel');

        Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPDF'])
            ->name('transactions.export.pdf');

        // =================================
        // SALDO
        // =================================
        Route::get('/saldo', [SaldoController::class, 'index'])
            ->name('saldo');

        Route::post('/withdraw', [SaldoController::class, 'store'])
            ->name('withdraw.store');

        // =================================
        // MERCH EO
        // =================================
        Route::resource('merch', EoMerchController::class);

        Route::get('/merch-transactions', [MerchTransactionController::class, 'index'])
            ->name('merch.transactions');

        Route::get('/merch-transactions/export/pdf', [MerchTransactionController::class, 'exportPDF'])
            ->name('merch.transactions.export.pdf');

        Route::get('/merch-transactions/export/excel', [MerchTransactionController::class, 'exportSimpleExcel'])
            ->name('merch.transactions.export.excel');

        // =================================
        // STATUS EVENT
        // =================================
        Route::get('/status', [EoEventController::class, 'status'])
            ->name('status');

        // =================================
        // EVENT KHUSUS
        // =================================

        Route::get(
            'event/{event}/edit-rejected',
            [EoEventController::class, 'editRejected']
        )->name('event.edit-rejected');

        Route::put(
            'event/{event}/resubmit',
            [EoEventController::class, 'resubmit']
        )->name('event.resubmit');

        // CANCEL
        Route::put(
            'event/{event}/request-cancel',
            [EoEventController::class, 'requestCancel']
        )->name('event.request-cancel');


        // RESCHEDULE FORM (GET MODAL)
        Route::get(
            'event/{event}/reschedule',
            [EoEventController::class, 'showRescheduleForm']
        )->name('event.reschedule.form');


        // RESCHEDULE SUBMIT (PUT)
        Route::put(
            'event/{event}/request-reschedule',
            [EoEventController::class, 'requestReschedule']
        )->name('event.request-reschedule');


            


        // =================================
        // RESOURCE EVENT
        // =================================
        Route::resource('event', EoEventController::class);
    });
});



/*
|--------------------------------------------------------------------------
| 4. TICKET & MERCHANDISE TRANSACTION ROUTES (Proses Pembelian & Payment Gateway)
|--------------------------------------------------------------------------
*/
Route::controller(TicketController::class)->group(function () {
    Route::get('/tiket', 'form')->name('ticket.form');
    Route::post('/tiket', 'store')->name('ticket.store');
    Route::get('/ticket/form', 'form')->name('ticket.form.alt');
    Route::get('/ticket/payment/{id}', 'payment')->name('ticket.payment');
    Route::post('/ticket/pay/{id}', 'processPayment')->name('ticket.pay');
    Route::post('/ticket/cancel/{id}', 'cancel')->name('ticket.cancel');
    Route::get('/tiket/success/{id}', 'success')->name('ticket.success');
    Route::get('/tiket/failed/{id}', 'failed')->name('ticket.failed');
});

Route::controller(MerchController::class)->group(function () {
    Route::get('/merch', 'index')->name('merch.index');
    Route::get('/merchandise/{event_id}', 'index')->name('merchandise.index');
    Route::post('/merch/checkout', 'checkout')->name('merch.checkout');
    Route::post('/merch/preview', 'preview')->name('merch.preview');
    Route::get('/merch/payment/{id}', 'processPayment')->name('merch.payment');
    Route::get('/merch/success/{id}', 'success')->name('merch.success');
    Route::get('/merch/failed/{id}', 'failed')->name('merch.failed');
});

// Webhook / Detail Ticket Callback
Route::get('/tickets/{id}', [WebhookController::class, 'show'])->name('tickets.show');


/*
|--------------------------------------------------------------------------
| 5. ABSENSI / ATTENDANCE ROUTES (Sisi Gatekeeper / Scanner)
|--------------------------------------------------------------------------
*/
Route::get('/absen/{kode}', [AbsensiController::class, 'showPasswordForm'])->name('absen.form');
Route::post('/absen/{kode}', [AbsensiController::class, 'handleScan'])->name('absen.submit');


/*
|--------------------------------------------------------------------------
| 6. ADMIN ROUTES (Manajemen Internal / Super Admin)
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin Dashboard & Exports
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/admin/dashboard', 'index')->name('admin.dashboard');
        Route::get('/admin/dashboard/export-excel', 'exportSimpleExcel')->name('admin.dashboard.export.excel');
        Route::get('/admin/dashboard/export-pdf', 'exportPDF')->name('admin.dashboard.export.pdf');
        Route::get('/admin/absensi', 'absensi')->name('admin.absensi');
        Route::post('/admin/transactions/{id}/regenerate-qr', 'regenerateQR')->name('admin.transactions.regenerateQR');
        Route::post('/admin/transactions/regenerate-qr', 'regenerateAllQR')->name('admin.transactions.regenerate-qr');
    });

    // Admin Merch Dashboard
    Route::controller(DashboardMerchController::class)->group(function () {
        Route::get('/admin/merch/dashboard', 'index')->name('admin.merch.dashboard');
        Route::get('/admin/merch/dashboard/export-excel', 'exportSimpleExcel')->name('admin.merch.dashboard.export.excel');
        Route::get('/admin/merch/dashboard/export-pdf', 'exportPDF')->name('admin.merch.dashboard.export.pdf');
        Route::post('/admin/merch/transactions/{id}/regenerate-qr', 'regenerateQR')->name('admin.merch.transactions.regenerateQR');
        Route::post('/admin/merch/transactions/regenerate-qr', 'regenerateAllQR')->name('admin.merch.transactions.regenerate-qr');
    });

    // Admin Attendance Actions
    Route::controller(AdminController::class)->group(function () {
        Route::post('/admin/absensi/{transaction}/mark', 'markPresence')->name('admin.absensi.mark');
        Route::post('/admin/absensi/{transaction}/cancel', 'cancelPresence')->name('admin.absensi.cancel');
        Route::post('/absensi/manual/{id}', 'absenManual')->name('admin.absensi.manual');
        Route::post('/absensi/batal/{id}', 'batalAbsen')->name('admin.absensi.batal');
        Route::get('/admin/attendee/{email}', 'showAttendeeDetail')->name('admin.attendee.detail');
    });

    // Admin Event Management
    Route::controller(AdminEventController::class)->group(function () {
        Route::get('/admin/event', 'index')->name('admin.event.index');
        Route::get('/admin/event/create', 'create')->name('admin.event.create');
        Route::post('/admin/event', 'store')->name('admin.event.store');
        Route::delete('/admin/event/{id}', 'destroy')->name('admin.event.destroy');
    });

    // Admin Merch Management
    Route::controller(AdminMerchController::class)->group(function () {
        Route::get('/admin/merch', 'index')->name('admin.merch.index');
        Route::post('/admin/merch', 'store')->name('admin.merch.store');
        Route::get('/admin/merch/{id}', 'show')->name('admin.merch.show');
        Route::get('/admin/merch/{id}/edit', 'edit')->name('admin.merch.edit');
        Route::put('/admin/merch/{id}', 'update')->name('admin.merch.update');
        Route::delete('/admin/merch/{id}', 'destroy')->name('admin.merch.destroy');
    });

    // QR & Guest Helpers
    Route::get('/tickets/{kode}', [TicketController::class, 'show'])->name('tickets.show_admin');
    Route::get('/guest/qr/{kode}', [GuestController::class, 'showQr'])->name('guests.qr');
    Route::get('/guest/{kode}/export-qr', [GuestController::class, 'exportGuestQR'])->name('guest.export.qr');
    Route::get('/guest/merch/qr/{kode_unik}', [MerchController::class, 'showQr'])->name('guests.merch.qr');
});


/*
|--------------------------------------------------------------------------
| 7. OWNER ROUTES (Persetujuan Level Tertinggi / Owner Platform)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/owner/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');

    // EO Approval (OwnerController)
    Route::get('/owner/eo', [OwnerController::class, 'eoIndex'])->name('owner.eo.index');
    Route::post('/owner/eo/{id}/approve', [OwnerController::class, 'approve'])->name('owner.eo.approve');
    Route::post('/owner/eo/{id}/reject', [OwnerController::class, 'reject'])->name('owner.eo.reject');

    // Event Approval & Cancel Management Group
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('/events', [EventApprovalController::class, 'index'])->name('events.index');
        Route::get('/events/{event}', [EventApprovalController::class, 'show'])->name('events.show');
        Route::post('/events/{event}/approve', [EventApprovalController::class, 'approve'])->name('events.approve');
        Route::post('/events/{event}/reject', [EventApprovalController::class, 'reject'])->name('events.reject');
        Route::put(
            '/events/{event}/approve-reschedule',
            [EventApprovalController::class, 'approveReschedule']
        )->name('events.approve-reschedule');

        Route::put(
            '/events/{event}/reject-reschedule',
            [EventApprovalController::class, 'rejectReschedule']
        )->name('events.reject-reschedule');
        
        // Persetujuan Pembatalan Event oleh Owner
        Route::put('/events/{event}/confirm-cancel', [EventApprovalController::class, 'confirmCancel'])->name('events.confirm-cancel');
        Route::put('/events/{event}/reject-cancel', [EventApprovalController::class, 'rejectCancel'])->name('events.reject-cancel');
    });

    // Withdrawal Approval Group
    Route::prefix('owner')->name('owner.')->group(function () {
        Route::get('/withdrawals', [WithdrawalApprovalController::class, 'index'])->name('withdrawals.index');
        Route::get('/withdrawals/{withdrawal}', [WithdrawalApprovalController::class, 'show'])->name('withdrawals.show');
        Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalApprovalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalApprovalController::class, 'reject'])->name('withdrawals.reject');
    });
});


/*
|--------------------------------------------------------------------------
| 8. FILE SERVING ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/qrcodes/{filename}', function ($filename) {
    $path = base_path('public_html/qrcodes/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
})->where('filename', '.*');