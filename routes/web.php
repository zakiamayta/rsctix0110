<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Controllers - Frontend & Umum
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\MerchController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AbsenMerchController;

// Controllers - Buyer Area
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BuyerRefundController;
use App\Http\Controllers\BuyerMerchRefundController;

// Controllers - Admin Area
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminMerchController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DashboardMerchController;
use App\Http\Controllers\Admin\AdminRefundController;
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\PlatformWalletController;
use App\Http\Controllers\Admin\AdminEventMonitoringController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RefundTransactionController;

// Controllers - Event Organizer (EO) Area
use App\Http\Controllers\Eo\EoController;
use App\Http\Controllers\Eo\EoEventController;
use App\Http\Controllers\Eo\EoDashboardController;
use App\Http\Controllers\Eo\EoMerchController;
use App\Http\Controllers\Eo\TransactionController;
use App\Http\Controllers\Eo\MerchTransactionController;
use App\Http\Controllers\Eo\TicketWithdrawalController;
use App\Http\Controllers\Eo\TicketHistoryController;
use App\Http\Controllers\Eo\MerchWithdrawalController;
use App\Http\Controllers\Eo\EoRefundController;
use App\Http\Controllers\Eo\EoFinanceController;

// Controllers - Owner Area
use App\Http\Controllers\Owner\OwnerController;
use App\Http\Controllers\Owner\EventApprovalController;
use App\Http\Controllers\Owner\WithdrawalApprovalController;
use App\Http\Controllers\Owner\MerchWithdrawalApprovalController;
use App\Http\Controllers\Owner\OwnerRefundMonitoringController;
use App\Http\Controllers\Owner\OwnerEventMonitoringController;
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

Route::get('/info/{id}', [InfoController::class, 'show'])->name('info.show');
Route::get('/band/negatifa', fn() => view('band.negatifa'))->name('band.negatifa');

/*
|--------------------------------------------------------------------------
| 2. GUEST & AUTHENTICATION ROUTES (GOOGLE)
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| 3. PROTECTED USER ROUTES (Harus Login Akun User)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    // ==========================
    // PROFILE USER & TRANSAKSI BUYER
    // ==========================
    Route::get('/complete-profile', [ProfileController::class, 'edit'])->name('profile.complete');
    Route::post('/complete-profile', [ProfileController::class, 'update'])->name('profile.complete.store');

    Route::get('/riwayat-tiket', [UserController::class, 'myTickets'])->name('user.tickets');
    Route::get('/riwayat-merch', [UserController::class, 'myMerch'])->name('user.merch');
    
    Route::get('/tickets/{id}/refund', [BuyerRefundController::class, 'create'])->name('buyer.refund.create');
    Route::post('/tickets/{id}/refund', [BuyerRefundController::class, 'store'])->name('buyer.refund.store');
    Route::get('/merch-refund/create/{id}', [BuyerMerchRefundController::class, 'create'])
        ->name('user.merch-refund.create');

    // Route untuk menyimpan/store Data Pengajuan Refund Merchandise
    Route::post('/merch-refund/store/{id}', [BuyerMerchRefundController::class, 'store'])
        ->name('user.merch-refund.store');

    // ==========================
    // REGISTRASI EO
    // ==========================
    Route::get('/eo/register', function () {
        $userId = Auth::id();
        $eo = DB::table('eo')->where('user_id', $userId)->first();

        if (!$eo) return view('eo.register');
        if ($eo->status === 'pending') return redirect()->route('eo.waiting');
        if ($eo->status === 'approved') return redirect()->route('eo.dashboard');
        if ($eo->status === 'rejected') {
            return view('eo.register')->with('error', 'Pengajuan sebelumnya ditolak, silakan daftar ulang');
        }
        return view('eo.register');
    })->name('eo.register');

    Route::post('/eo/register', [EoController::class, 'store'])->name('eo.store');
    Route::get('/eo/waiting', [EoController::class, 'waiting'])->name('eo.waiting');

    // Shared Route Antara Admin dan Owner
    Route::get('/global-platform-wallet', [PlatformWalletController::class, 'index'])->name('platform.wallet.index');

    /*
    |--------------------------------------------------------------------------
    | 4. EO (EVENT ORGANIZER) AREA - PREFIX GROUP
    |--------------------------------------------------------------------------
    */
    Route::prefix('eo')->name('eo.')->group(function () {
        
        // Dasbor & Profil
        
        Route::get('/dashboard', [EoDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profile', [EoController::class, 'profile'])->name('profile');
        Route::post('/profile', [EoController::class, 'updateProfile'])->name('profile.update');

        // Transaksi & Laporan Tiket
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');
        Route::get('/transactions/export-excel', [TransactionController::class, 'exportSimpleExcel'])->name('transactions.export.excel');
        Route::get('/transactions/export-pdf', [TransactionController::class, 'exportPDF'])->name('transactions.export.pdf');

        // Dompet & Penarikan Dana Tiket
        Route::get('/ticket-wallet', [TicketWithdrawalController::class, 'index'])->name('ticket-wallet.dashboard');
        Route::get('/ticket-withdraw/{eventId}', [TicketWithdrawalController::class, 'create'])->name('ticket-withdraw.create');
        Route::post('/ticket-withdraw', [TicketWithdrawalController::class, 'store'])->name('ticket-withdraw.store');
        Route::get('/ticket-history', [TicketHistoryController::class, 'index'])->name('ticket-history.index');
        Route::get('/ticket-history/{id}', [TicketHistoryController::class, 'show'])->name('ticket-history.show');
        Route::get('ticket-withdraw/tickets/{eventId}', [TicketWithdrawalController::class, 'soldTickets'])->name('ticket-withdraw.tickets');

        // Dompet & Penarikan Dana Merchandise
        Route::get('/merch-wallet', [MerchWithdrawalController::class, 'index'])->name('merch-wallet.dashboard');
        Route::get('/merch-wallet/create/{eventId}', [MerchWithdrawalController::class, 'create'])->name('merch-withdrawal.create');
        Route::post('/merch-wallet/store', [MerchWithdrawalController::class, 'store'])->name('merch-withdrawal.store');
        Route::get('/merch-wallet/detail/{id}', [MerchWithdrawalController::class, 'show'])->name('merch-withdrawal.show');
        Route::get('merch-withdraw/history', [MerchWithdrawalController::class, 'history'])->name('merch-withdraw.history');
        Route::get('merch-withdraw/history/{id}', [MerchWithdrawalController::class, 'showDetailHistory'])->name('merch-withdraw.history.detail');
        Route::get('merch-withdraw/products/{eventId}', [MerchWithdrawalController::class, 'soldProducts'])->name('merch-withdraw.products');

        // Manajemen Dagangan & Riwayat Transaksi Merchandise EO
        Route::resource('merch', EoMerchController::class);
        Route::get('/merch-transactions', [MerchTransactionController::class, 'index'])->name('merch.transactions');
        Route::get('/merch-transactions/export/pdf', [MerchTransactionController::class, 'exportPDF'])->name('merch.transactions.export.pdf');
        Route::get('/merch-transactions/export/excel', [MerchTransactionController::class, 'exportSimpleExcel'])->name('merch.transactions.export.excel');

        // Pengajuan Perubahan Event / Krisis
        Route::get('/status', [EoEventController::class, 'status'])->name('status');
        Route::get('event/{event}/edit-rejected', [EoEventController::class, 'editRejected'])->name('event.edit-rejected');
        Route::put('event/{event}/resubmit', [EoEventController::class, 'resubmit'])->name('event.resubmit');
        Route::put('event/{event}/request-cancel', [EoEventController::class, 'requestCancel'])->name('event.request-cancel');
        Route::get('event/{event}/reschedule', [EoEventController::class, 'showRescheduleForm'])->name('event.reschedule.form');
        Route::put('event/{event}/request-reschedule', [EoEventController::class, 'requestReschedule'])->name('event.request-reschedule');
        Route::resource('event', EoEventController::class);
        // Sisi EO Dashboard (Pastikan ditaruh di dalam group middleware auth/eo Anda)
        Route::post('event/{event}/merch-decision', [EoEventController::class, 'submitMerchDecision'])->name('event.submit-merch-decision');
    
        // Pantauan Lapangan Sisi EO
        Route::get('/absensi/tiket', [AbsensiController::class, 'indexPantauan'])->name('absensi.tiket');
        Route::post('/absensi/manual/{id}', [AbsensiController::class, 'absenManual'])->name('absensi.manual');
        Route::post('/absensi/batal/{id}', [AbsensiController::class, 'batalAbsen'])->name('absensi.batal');
        Route::get('/absensi/merch', [AbsenMerchController::class, 'indexMerch'])->name('absensi.merch');
        Route::post('/absensi/merch/manual/{id}', [AbsenMerchController::class, 'merchManual'])->name('absensi.merch.manual');
        Route::post('/absensi/merch/batal/{id}', [AbsenMerchController::class, 'batalMerch'])->name('absensi.merch.batal');

        // Transparansi Refund Sisi EO
        Route::get('/refunds', [EoRefundController::class, 'index'])->name('refunds.index');
        Route::get('/refunds/batch/{id}', [EoRefundController::class, 'showBatchDetails'])->name('refunds.show');

        // Ruang Keuangan & Top Up Mandiri EO
        Route::get('/finance', [EoFinanceController::class, 'index'])->name('finance.index');
        Route::post('/finance/upload-proof/{topupId}', [EoFinanceController::class, 'uploadProof'])->name('finance.uploadProof');

        
    });

    /*
    |--------------------------------------------------------------------------
    | 5. ADMIN AREA - PREFIX GROUP
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {

        // Admin Dashboard & Exports Utama
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/dashboard', 'index')->name('dashboard');
            Route::get('/transactions', 'transactions')->name('transactions');
            Route::get('/dashboard/export-excel', 'exportSimpleExcel')->name('dashboard.export.excel');
            Route::get('/dashboard/export-pdf', 'exportPDF')->name('dashboard.export.pdf');
            Route::post('/transactions/{id}/regenerate-qr', 'regenerateQR')->name('transactions.regenerateQR');
            Route::post('/transactions/regenerate-qr', 'regenerateAllQR')->name('transactions.regenerate-qr');
        });
        Route::controller(UserManagementController::class)->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::patch('/users/{id}/update-role', [UserManagementController::class, 'updateRole'])->name('users.updateRole');
        });
        

        // Admin Merchandise Dashboard & Laporan
        Route::controller(DashboardMerchController::class)->group(function () {
            Route::get('/merch/dashboard', 'index')->name('merch.dashboard');
            Route::get('/merch/dashboard/export-excel', 'exportSimpleExcel')->name('merch.dashboard.export.excel');
            Route::get('/merch/dashboard/export-pdf', 'exportPDF')->name('merch.dashboard.export.pdf');
            Route::post('/merch/transactions/{id}/regenerate-qr', 'regenerateQR')->name('merch.transactions.regenerateQR');
            Route::post('/merch/transactions/regenerate-qr', 'regenerateAllQR')->name('merch.transactions.regenerate-qr');
        });

        // Validasi Kehadiran & Pantauan Absensi Admin
        Route::controller(AbsensiController::class)->group(function () {
            Route::get('/absensi', 'indexPantauan')->name('absensi');
            Route::post('/absensi/manual/{id}', 'absenManual')->name('absensi.manual');
            Route::post('/absensi/batal/{id}', 'batalAbsen')->name('absensi.batal');
        });

        Route::get('/refund-transactions', [RefundTransactionController::class, 'index'])->name('refund.transactions');

        // Route::controller(AdminController::class)->group(function () {
        //     Route::post('/absensi/{transaction}/mark', 'markPresence')->name('absensi.mark');
        //     Route::post('/absensi/{transaction}/cancel', 'cancelPresence')->name('absensi.cancel');
        //     Route::get('/attendee/{email}', 'showAttendeeDetail')->name('attendee.detail');
        // });

        // Kelola Data Event & Data Merchandise Platform
        Route::controller(AdminEventController::class)->group(function () {
            Route::get('/event', 'index')->name('event.index');
            Route::get('/event/create', 'create')->name('event.create');
            Route::post('/event', 'store')->name('event.store');
            Route::delete('/event/{id}', 'destroy')->name('event.destroy');
        });

        Route::controller(AdminMerchController::class)->group(function () {
            Route::get('/merch', 'index')->name('merch.index');
            Route::post('/merch', 'store')->name('merch.store');
            Route::get('/merch/{id}', 'show')->name('merch.show');
            Route::get('/merch/{id}/edit', 'edit')->name('merch.edit');
            Route::put('/merch/{id}', 'update')->name('merch.update');
            Route::delete('/merch/{id}', 'destroy')->name('merch.destroy');
        });

        // Eksekusi Pemrosesan Batch Refund Pembatalan Event
        Route::controller(AdminRefundController::class)->group(function () {
            Route::get('/refunds', 'index')->name('refunds.index');
            Route::post('/refunds/batch', 'storeBatch')->name('refunds.storeBatch');
            Route::get('/refunds/batch/{id}', 'show')->name('refunds.show');
            Route::post('/refunds/batch/{id}/complete', 'completeBatch')->name('refunds.completeBatch');
            Route::get('/refunds/batch/{id}/export-xendit', 'exportXendit')->name('refunds.exportXendit');
            Route::patch('/refunds/batch/{id}/toggle-status', 'toggleStatus')->name('refunds.toggleStatus');
            Route::post('/refunds/batch/{id}/send-xendit', 'sendToXendit')->name('refunds.sendToXendit');
            Route::post('/refunds/item/{id}/retry', 'retryRefund')->name('refunds.item.retry');
            Route::patch('/refunds/item/{id}/reject', 'rejectRefund')->name('refunds.item.reject');
            Route::post('/refunds/item/{id}/sync', 'syncStatus')->name('refunds.item.sync');
        });

        // Ruang Kendali Finansial & Dompet Audit EO
        Route::controller(AdminFinanceController::class)->group(function () {
            Route::get('finance', 'index')->name('finance.index');
            Route::get('finance/event/{event}', 'manageEvent')->name('finance.manageEvent');
            Route::post('finance/event/{event}/request-topup', 'requestTopup')->name('finance.requestTopup');
            Route::post('finance/topup/{id}/{status}', 'verifyTopup')->name('finance.verifyTopup');
        });

    });
    Route::prefix('admin/monitoring')->name('admin.monitoring.')->middleware('auth')->group(function () {
        Route::get('/', [AdminEventMonitoringController::class, 'index'])->name('index');
        Route::get('/eo/{eo}', [AdminEventMonitoringController::class, 'showEo'])->name('eo.show');
        Route::get('/event/{event}/summary', [AdminEventMonitoringController::class, 'eventSummary'])->name('event.summary');
    });

    /*
    |--------------------------------------------------------------------------
    | 6. OWNER AREA - PREFIX GROUP
    |--------------------------------------------------------------------------
    */
    Route::prefix('owner')->name('owner.')->group(function () {

        // Dashboard & Approval Pendaftaran Akun EO
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('dashboard');
        Route::get('/eo', [OwnerController::class, 'eoIndex'])->name('eo.index');
        Route::post('/eo/{id}/approve', [OwnerController::class, 'approve'])->name('eo.approve');
        Route::post('/eo/{id}/reject', [OwnerController::class, 'reject'])->name('eo.reject');

        // Verifikasi Izin Publikasi Event, Reschedule, dan Pembatalan
        Route::controller(EventApprovalController::class)->group(function () {
            Route::get('/events', 'index')->name('events.index');
            Route::get('/events/{event}', 'show')->name('events.show');
            Route::post('/events/{event}/approve', 'approve')->name('events.approve');
            Route::post('/events/{event}/reject', 'reject')->name('events.reject');
            Route::put('/events/{event}/approve-reschedule', 'approveReschedule')->name('events.approve-reschedule');
            Route::put('/events/{event}/reject-reschedule', 'rejectReschedule')->name('events.reject-reschedule');
            Route::put('/events/{event}/confirm-cancel', 'confirmCancel')->name('events.confirm-cancel');
            Route::put('/events/{event}/reject-cancel', 'rejectCancel')->name('events.reject-cancel');
        });

        // Audit Pengawasan Jalannya Prosedur Pengembalian Dana
        Route::get('/refund-monitoring', [OwnerRefundMonitoringController::class, 'index'])->name('refunds.monitor');
        Route::get('/refund-monitoring/batch/{id}', [OwnerRefundMonitoringController::class, 'showBatchDetails'])->name('refunds.monitor.show');

        // Validasi & Pencairan Dana (Withdrawals) Tiket & Merchandise
        Route::get('/withdrawals', [WithdrawalApprovalController::class, 'index'])->name('withdrawals.index');
        
        Route::get('withdrawals/merch', [MerchWithdrawalApprovalController::class, 'index'])->name('withdrawals.merch.index');
        Route::get('withdrawals/merch/{id}', [MerchWithdrawalApprovalController::class, 'show'])->name('withdrawals.merch.show');
        Route::post('withdrawals/merch/{id}/approve', [MerchWithdrawalApprovalController::class, 'approve'])->name('withdrawals.merch.approve');
        Route::post('withdrawals/merch/{id}/reject', [MerchWithdrawalApprovalController::class, 'reject'])->name('withdrawals.merch.reject');

        Route::get('/withdrawals/{withdrawal}', [WithdrawalApprovalController::class, 'show'])->name('withdrawals.show');
        Route::post('/withdrawals/{withdrawal}/approve', [WithdrawalApprovalController::class, 'approve'])->name('withdrawals.approve');
        Route::post('/withdrawals/{withdrawal}/reject', [WithdrawalApprovalController::class, 'reject'])->name('withdrawals.reject');

        Route::get('/monitoring', [OwnerEventMonitoringController::class, 'index'])->name('monitoring.index');
        Route::get('/monitoring/eo/{eoId}', [OwnerEventMonitoringController::class, 'showEo'])->name('monitoring.eo.show');
        Route::get('/monitoring/event/{eventId}/summary', [OwnerEventMonitoringController::class, 'eventSummary'])->name('monitoring.event.summary');
    });
});

/*
|--------------------------------------------------------------------------
| 7. TRANSACTION PROCESSING, GATEKEEPER SCANNER & EXTERNAL WEBHOOKS
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
    Route::get('/merchandise/{event_id}', 'index')->name('merch.index');
    Route::post('/merch/checkout', 'checkout')->name('merch.checkout');
    Route::get('/merch/checkout', 'showCheckout')->name('merch.checkout.show');
    Route::post('/merch/preview', 'preview')->name('merch.preview');
    Route::get('/merch/payment/{id}', 'processPayment')->name('merch.payment');
    Route::get('/merch/success/{id}', 'success')->name('merch.success');
    Route::get('/merch/failed/{id}', 'failed')->name('merch.failed');
});

// Penukaran Merchandise Sisi Validasi Staff Gatekeeper Lapangan
Route::post('/guest/merch/qr/{kode_unik}/verify', [AbsenMerchController::class, 'verify'])->name('admin.absen.verify-merch');
Route::post('/guest/merch/qr/{kode_unik}/store', [AbsenMerchController::class, 'store'])->name('admin.absen.store-merch');

// Webhook Checker & Scanner Lapangan Publik
Route::get('/tickets/{id}', [WebhookController::class, 'show'])->name('tickets.show');
Route::get('/absen/{kode}', [AbsensiController::class, 'showPasswordForm'])->name('absen.form');
Route::post('/absen/{kode}', [AbsensiController::class, 'handleScan'])->name('absen.submit');

Route::post('/webhook/xendit-payout', [WebhookController::class, 'handlePayoutCallback'])->name('webhook.xendit.payout');

// QR Helpers Publik
Route::get('/tickets/view/{kode}', [TicketController::class, 'show'])->name('tickets.show_admin');
Route::get('/guest/qr/{kode}', [GuestController::class, 'showQr'])->name('guests.qr');
Route::get('/guest/{kode}/export-qr', [GuestController::class, 'exportGuestQR'])->name('guest.export.qr');
Route::get('/guest/merch/qr/{kode_unik}', [MerchController::class, 'showQr'])->name('guests.merch.qr');

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

// Route::get('/qrcodes/{filename}', function ($filename) {
//     $path = base_path('public_html/qrcodes/' . $filename);
//     if (!file_exists($path)) abort(404);
//     return response()->file($path);
// })->where('filename', '.*');

// // 🔥 PINTU MASUK AUTO-LOGIN FLUTTER (VERSI AMAN TANPA PERLU IMPORT DI ATAS FILE)
// // 🔥 PINTU MASUK AUTO-LOGIN (VERSI KUAT DENGAN PENGUNCI SESI & DEBUG)
// Route::get('/autologin', function () {
//     $token = request()->query('token');

//     if (!$token) {
//         return response("Error: Token tidak ditemukan pada URL.", 400);
//     }

//     // 1. Ambil ID User dari Cache
//     $userId = \Illuminate\Support\Facades\Cache::get('web_autologin_' . $token);

//     // 🔍 KONDISI DEBUG 1: Jika Token tidak ditemukan di Cache
//     if (!$userId) {
//         return response()->json([
//             'status' => 'Gagal Auto-Login',
//             'alasan' => 'Token sudah kedaluwarsa (di atas 2 menit) atau Cache antar-driver tidak sinkron.',
//             'token_yang_dikirim_flutter' => $token
//         ], 403);
//     }

//     // 2. Eksekusi Login Menggunakan ID
//     // Tambahkan parameter 'true' di belakang agar Laravel membuat 'Remember Me' cookie yang lebih persisten
//     $loginSukses = \Illuminate\Support\Facades\Auth::loginUsingId($userId, true);

//     // 🔍 KONDISI DEBUG 2: Jika ID user ternyata tidak ada di tabel users
//     if (!$loginSukses) {
//         return response("Error: User dengan ID: {$userId} tidak ditemukan di database.", 404);
//     }

//     // 3. 🔥 JALUR CRUCIAL: Kunci Sesi ke Browser (Wajib untuk Cloudflare Tunnel)
//     request()->session()->regenerate();
//     request()->session()->save();

//     // 4. Hapus token dari cache setelah berhasil digunakan demi keamanan
//     \Illuminate\Support\Facades\Cache::forget('web_autologin_' . $token);

//     // 5. Alihkan ke halaman dashboard tujuan
//     return redirect('/eo/event'); 
// });

use Illuminate\Support\Facades\Http;

Route::get('/xendit-bank', function () {

    $response = Http::withBasicAuth(
        env('XENDIT_API_KEY'),
        ''
    )->get('https://api.xendit.co/payouts_channels', [
        'channel_category' => 'BANK',
        'currency' => 'IDR',
    ]);

    $banks = [];

    foreach ($response->json() as $bank) {

        $banks[$bank['channel_name']]
            = $bank['channel_code'];

    }

    ksort($banks);

    return response()->json($banks);

});