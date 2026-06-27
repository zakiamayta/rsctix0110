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
use App\Http\Controllers\AbsenMerchController;
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
use App\Http\Controllers\Eo\TicketWithdrawalController;
use App\Http\Controllers\Eo\TicketHistoryController;
use App\Http\Controllers\Eo\MerchWithdrawalController;
use App\Http\Controllers\Owner\MerchWithdrawalApprovalController;
use App\Http\Controllers\BuyerRefundController;
use App\Http\Controllers\Admin\AdminRefundController;
use App\Http\Controllers\Owner\OwnerRefundMonitoringController;
use App\Http\Controllers\Eo\EoRefundController;
use App\Http\Controllers\Admin\PlatformWalletController;


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
Route::get('/login',[
    LoginController::class,
    'showLoginForm'
])->name('login');


Route::get(
'/auth/google',
[GoogleAuthController::class,'redirect']
)->name('google.login');


Route::get(
'/auth/google/callback',
[GoogleAuthController::class,'callback']
);

// Logout User
// Route::post('/user/logout', function () {
//     Auth::guard('user')->logout();
//     return redirect('/');
// })->name('user.logout');
Route::post('/logout', function () {

    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect('/');

})->name('logout');


/*
|--------------------------------------------------------------------------
| 3. PROTECTED USER ROUTES (Harus Login Akun User)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

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

    Route::get('/tickets/{id}/refund', [BuyerRefundController::class, 'create'])->name('buyer.refund.create');
    Route::post('/tickets/{id}/refund', [BuyerRefundController::class, 'store'])->name('buyer.refund.store');

    // ==========================
    // REGISTRASI EO
    // ==========================
    Route::get('/eo/register', function () {

        $userId = Auth::id();

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

        // // =================================
        // // SALDO
        // // =================================
        // // Rute Finansial & Dompet Saldo (Grouped)
        //         Route::get(
        //     '/ticket-wallet',
        //     [TicketWalletController::class, 'dashboard']
        // )->name('ticket-wallet.dashboard');


        Route::get('/ticket-withdraw/{eventId}',[TicketWithdrawalController::class, 'create'])->name('ticket-withdraw.create');
        Route::post('/ticket-withdraw',[TicketWithdrawalController::class, 'store'])->name('ticket-withdraw.store');
        Route::get('/ticket-history',[TicketHistoryController::class, 'index'])->name('ticket-history.index');
        Route::get('/ticket-history/{id}',[TicketHistoryController::class, 'show'])->name('ticket-history.show');
        Route::get('/ticket-wallet',[TicketWithdrawalController::class, 'index'])->name('ticket-wallet.dashboard');
        Route::get('ticket-withdraw/tickets/{eventId}', [TicketWithdrawalController::class, 'soldTickets'])->name('eo.ticket-withdraw.tickets');

        Route::get('/merch-wallet', [MerchWithdrawalController::class, 'index'])->name('merch-wallet.dashboard');
        Route::get('/merch-wallet/create/{eventId}', [MerchWithdrawalController::class, 'create'])->name('merch-withdrawal.create');
        Route::post('/merch-wallet/store', [MerchWithdrawalController::class, 'store'])->name('merch-withdrawal.store');
        Route::get('/merch-wallet/detail/{id}', [MerchWithdrawalController::class, 'show'])->name('merch-withdrawal.show');
        Route::get('merch-withdraw/history', [MerchWithdrawalController::class, 'history'])->name('merch-withdraw.history');
        Route::get('merch-withdraw/history/{id}', [MerchWithdrawalController::class, 'showDetailHistory'])->name('merch-withdraw.history.detail');
        Route::get('merch-withdraw/products/{eventId}', [MerchWithdrawalController::class, 'soldProducts'])->name('eo.merch-withdraw.products');

        // =================================
        // MERCH DASHBOARD EO
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
        // PANTAUAN ABSENSI & MERCHANDISE (SISI EO)
        // =================================
        // 🎫 Sisi Tiket
        Route::get('/absensi/tiket', [AbsensiController::class, 'indexPantauan'])->name('absensi.tiket');
        Route::post('/absensi/manual/{id}', [AbsensiController::class, 'absenManual'])->name('absensi.manual');
        Route::post('/absensi/batal/{id}', [AbsensiController::class, 'batalAbsen'])->name('absensi.batal');

       // 📦 Sisi Penukaran Merchandise EO (Sekarang diarahkan ke AbsenMerchController)
// 📦 Sisi Penukaran Merchandise EO
        Route::get('/absensi/merch', [AbsenMerchController::class, 'indexMerch'])->name('absensi.merch');
        Route::post('/absensi/merch/manual/{id}', [AbsenMerchController::class, 'merchManual'])->name('absensi.merch.manual');
        Route::post('/absensi/merch/batal/{id}', [AbsenMerchController::class, 'batalMerch'])->name('absensi.merch.batal');


            


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
// Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin Dashboard & Exports
    Route::controller(DashboardController::class)->group(function () {
        Route::get('/admin/dashboard', 'index')->name('admin.dashboard');
        Route::get('/admin/dashboard/export-excel', 'exportSimpleExcel')->name('admin.dashboard.export.excel');
        Route::get('/admin/dashboard/export-pdf', 'exportPDF')->name('admin.dashboard.export.pdf');
        Route::post('/admin/transactions/{id}/regenerate-qr', 'regenerateQR')->name('admin.transactions.regenerateQR');
        Route::post('/admin/transactions/regenerate-qr', 'regenerateAllQR')->name('admin.transactions.regenerate-qr');
    });

    // SINKRONISASI: Satukan rute pantauan absensi Admin ke AbsensiController utama
    Route::controller(AbsensiController::class)->group(function () {
        Route::get('/admin/absensi', 'indexPantauan')->name('admin.absensi');
        Route::post('/admin/absensi/manual/{id}', 'absenManual')->name('admin.absensi.manual'); // URL diperbaiki dengan /admin
        Route::post('/admin/absensi/batal/{id}', 'batalAbsen')->name('admin.absensi.batal');   // URL diperbaiki dengan /admin
    });

    // Tambahkan rute POST ini di dalam web.php Anda (di kelompok Admin/Auth)
// URL-nya disesuaikan dengan kebutuhan form-merch.blade.php Anda tanpa mengubah jalur scan awal
Route::post('/guest/merch/qr/{kode_unik}/verify', [App\Http\Controllers\Admin\AbsenMerchController::class, 'verify'])->name('admin.absen.verify-merch');
Route::post('/guest/merch/qr/{kode_unik}/store', [App\Http\Controllers\Admin\AbsenMerchController::class, 'store'])->name('admin.absen.store-merch');

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


            // ==========================================
            // 👨‍✈️ 1. GRUP ROUTE: ADMIN UTAMA (EKSEKUTOR)
            // ==========================================
            Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
                
                // Halaman utama daftar batch & form buka batch
                Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
                
                // Aksi untuk membuka batch refund baru per event
                Route::post('/refunds/batch', [AdminRefundController::class, 'storeBatch'])->name('refunds.storeBatch');
                
                // Melihat rincian rekening penonton di dalam batch tertentu
                Route::get('/refunds/batch/{id}', [AdminRefundController::class, 'show'])->name('refunds.show');
                
                // Aksi tombol centang (Menyelesaikan batch & kalkulasi potong saldo/utang EO)
                Route::post('/refunds/batch/{id}/complete', [AdminRefundController::class, 'completeBatch'])->name('refunds.completeBatch');
                Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
                Route::get('/refunds/batch/{id}', [AdminRefundController::class, 'show'])->name('refunds.show');

                // 🌟 RUTE BARU: Export Excel Berdasarkan ID Batch Refund
                //  KODE BARU YANG SUDAH SINKRON:
                Route::get('/refunds/batch/{id}/export-xendit', [AdminRefundController::class, 'exportXendit'])->name('refunds.exportXendit');
                Route::patch('/refunds/batch/{id}/toggle-status', [AdminRefundController::class, 'toggleStatus'])->name('refunds.toggleStatus');
                
            });


            // ==========================================
            // 👑 2. GRUP ROUTE: OWNER UTAMA (MONITORING)
            // ==========================================
            Route::middleware(['auth'])->prefix('owner')->name('owner.')->group(function () {
                
                // Halaman utama dashboard pengawasan audit refund & list utang EO global
                Route::get('/refund-monitoring', [OwnerRefundMonitoringController::class, 'index'])->name('refunds.monitor');
                
                // Melihat rincian data batch tertentu tanpa tombol aksi eksekusi (read-only)
                Route::get('/refund-monitoring/batch/{id}', [OwnerRefundMonitoringController::class, 'showBatchDetails'])->name('refunds.monitor.show');
                
            });


            // ==========================================
            // 🤝 3. GRUP ROUTE: EVENT ORGANIZER (TRANSPARANSI)
            // ==========================================
            Route::middleware(['auth'])->prefix('eo')->name('eo.')->group(function () {
                
                // Halaman utama EO melihat status refund event mereka & rekap log utang sendiri
                Route::get('/refunds', [EORefundController::class, 'index'])->name('refunds.index');
                
                // Melihat data penonton mereka yang mengajukan refund (tanpa nomor rekening penuh)
                Route::get('/refunds/batch/{id}', [EORefundController::class, 'showBatchDetails'])->name('refunds.show');

                
                
            });

            // Pastikan user sudah login terlebih dahulu
Route::middleware(['auth'])->group(function () {
    
    // Route Dompet Platform yang dishare untuk Admin dan Owner
    Route::get('/global-platform-wallet', [PlatformWalletController::class, 'index'])->name('platform.wallet.index');

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


// =========================================================================
    // Withdrawal Approval Group (SUDAH DIPERBAIKI URUTANNYA)
    // =========================================================================
    Route::prefix('owner')->name('owner.')->group(function () {
        
        // 1. Letakkan rute index utama tiket paling atas
        Route::get('/withdrawals', [WithdrawalApprovalController::class, 'index'])->name('withdrawals.index');
        
        // 2. Letakkan rute Merchandise DISINI (Sebelum rute wildcard {})
        Route::get('withdrawals/merch', [MerchWithdrawalApprovalController::class, 'index'])->name('withdrawals.merch.index');
        Route::get('withdrawals/merch/{id}', [MerchWithdrawalApprovalController::class, 'show'])->name('withdrawals.merch.show');
        Route::post('withdrawals/merch/{id}/approve', [MerchWithdrawalApprovalController::class, 'approve'])->name('withdrawals.merch.approve');
        Route::post('withdrawals/merch/{id}/reject', [MerchWithdrawalApprovalController::class, 'reject'])->name('withdrawals.merch.reject');

        // 3. Letakkan rute detail wildcard tiket PALING BAWAH agar tidak memotong rute merch
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