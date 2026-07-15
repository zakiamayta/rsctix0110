<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\GoogleMobileController;
use App\Http\Controllers\Api\EODashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DetailEventController;
use App\Http\Controllers\Api\MerchController;
use App\Http\Controllers\Api\EOTicketController;
use App\Http\Controllers\Api\EOMerchController;
use App\Http\Controllers\Api\OwnerDashboardController;
use App\Http\Controllers\Api\OwnerApprovalController;
use App\Http\Controllers\Api\OwnerMerchController;
use App\Http\Controllers\Api\OwnerTicketController;
use App\Http\Controllers\Api\OwnerWalletController;
use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminPlatformWalletController;
use App\Http\Controllers\Api\OwnerPlatformRevenue;
use App\Http\Controllers\Api\AdminRevenueController;
use App\Http\Controllers\Api\AdminCustomerManagementController;
use App\Http\Controllers\Api\AdminEventController;
use App\Http\Controllers\Api\AdminWalletController;
use App\Http\Controllers\Api\EoRefundTransactionController;
use App\Http\Controllers\Api\EoTransactionController;

/*
|--------------------------------------------------------------------------
| PUBLIC API
|--------------------------------------------------------------------------
*/

/// LOGO
Route::get(
    '/logo',
    [GoogleMobileController::class, 'logo']
);
Route::get('/refunds/banks', [HomeApiController::class, 'listBanks']);
/// GOOGLE LOGIN
Route::post(
    '/google-login',
    [GoogleMobileController::class, 'login']
);

/// XENDIT WEBHOOK
Route::post(
    '/xendit/webhook',
    [WebhookController::class, 'handleCallback']
);

/// XENDIT WEBHOOK - PAYOUT REFUND (BARU)
Route::post(
    '/xendit/webhook-payout',
    [WebhookController::class, 'handlePayoutCallback']
);

/// HOME
Route::get(
    '/home',
    [HomeApiController::class, 'index']
);
Route::get('/notifications', [App\Http\Controllers\Api\HomeApiController::class, 'notifications']);
Route::middleware('auth:sanctum')->post('/refunds/submit', [HomeApiController::class, 'submitRefund']);
Route::middleware('auth:sanctum')->get('/refunds/detail', [HomeApiController::class, 'refundDetail']);
/// EVENTS
Route::get(
    '/events',
    [EventController::class, 'index']
);

/// EVENT DETAIL
Route::get(
    '/event-detail/{id}',
    [DetailEventController::class, 'show']
);

/// TRANSACTION DETAIL
Route::get(
    '/transaction/{id}',
    [TicketController::class, 'detail']
);

/// MERCH LIST
Route::get(
    '/merch/{eventId}',
    [MerchController::class, 'index']
);

/// MERCH TRANSACTION DETAIL
Route::get(
    '/transaction-merch/{id}',
    [MerchController::class, 'detail']
);

/*
|--------------------------------------------------------------------------
| AUTHENTICATED API
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    /// GET PROFILE
    Route::get(
        '/profile',
        [GoogleMobileController::class, 'profile']
    );

    /// UPDATE PROFILE
    Route::post(
        '/update-profile',
        [GoogleMobileController::class, 'updateProfile']
    );

    /// LOGOUT
    Route::post(
        '/logout',
        [GoogleMobileController::class, 'logout']
    );

    /*
    |--------------------------------------------------------------------------
    | EO DASHBOARD
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/eo/dashboard',
        [EODashboardController::class, 'index']
    );

    /*
    |--------------------------------------------------------------------------
    | CHECKOUT
    |--------------------------------------------------------------------------
    */

    /// TICKET CHECKOUT
    Route::post(
        '/checkout',
        [TicketController::class, 'checkout']
    );

    /// MERCH CHECKOUT
    Route::post(
        '/merch/checkout',
        [MerchController::class, 'checkout']
    );

    /*
    |--------------------------------------------------------------------------
    | USER ORDER
    |--------------------------------------------------------------------------
    */

    /// ORDER HISTORY
    Route::get(
        '/order-history',
        [HomeApiController::class, 'orderHistory']
    );

    /// MY TICKETS
    Route::get(
        '/my-tickets',
        [TicketController::class, 'myTickets']
    );

    /// MY MERCH
    Route::get(
        '/my-merch',
        [MerchController::class, 'myMerch']
    );

    Route::get('/ticket-sales', [EODashboardController::class, 'ticketSales']);
    Route::get('/ticket-sales/{id}', [EODashboardController::class, 'ticketSalesDetail']);
    Route::get('/eo/real-revenue', [EODashboardController::class, 'getRealRevenue']);
    Route::get('/eo/outgoing-transactions', [EoTransactionController::class, 'getOutgoingTransactions']);
    /*
    |--------------------------------------------------------------------------
    | EO WITHDRAWAL SYSTEM (SINKRON FRONT-END)
    |--------------------------------------------------------------------------
    */
    // Ambil daftar wallets berdasarkan EO ID
    Route::get('/eo/{eoId}/event-wallets', [EOTicketController::class, 'eventWallets']);
    
    // Dashboard withdrawal
    Route::get('/eo-withdrawal-dashboard/{eoId}', [EOTicketController::class, 'dashboard']);
    
    // Ambil Riwayat Withdrawal Tiket
    Route::get('/withdrawals', [EOTicketController::class, 'index']); 
    
    // Kirim pengajuan withdrawal tiket baru
    Route::post('/request-withdraw', [EOTicketController::class, 'requestWithdraw']); 

    /*
    |--------------------------------------------------------------------------
    | EO MERCHANDISE WITHDRAWAL SYSTEM
    |--------------------------------------------------------------------------
    */
    // 1. Endpoint Statistik Dompet Merch
    Route::get('/merch-stats/{eoId}', [EOMerchController::class, 'merchWallets']);
    // 2. Endpoint Riwayat Penarikan Dana Merchandise
    Route::get('/merch-withdrawals', [EOMerchController::class, 'index']);
    // 3. Endpoint Eksekusi Pengajuan Tarik Dana Merch + Upload Invoice
    Route::post('/merch/withdraw', [EOMerchController::class, 'requestMerchWithdraw']);
    Route::get('/merch-sales', [EOMerchController::class, 'getMerchSales']); 
    Route::get('/merch-sales/{transactionId}', [EOMerchController::class, 'show']);

    Route::post('/eo/generate-web-token', [EODashboardController::class, 'generateWebToken']);
    Route::get('/eo/sales-recap', [EODashboardController::class, 'getSalesRecap']);
    /*
    |--------------------------------------------------------------------------
    | OWNER SYSTEM API
    |--------------------------------------------------------------------------
    */
    Route::get('/owner/dashboard', [OwnerDashboardController::class, 'index']);
    Route::get('/owner/approval/eo', [OwnerApprovalController::class, 'index']);
    Route::post('/owner/approval/eo/{id}', [OwnerApprovalController::class, 'processApproval']);
    Route::get('/owner/approval/events', [OwnerApprovalController::class, 'indexEvents']);
    Route::post('/owner/approval/events/{id}', [OwnerApprovalController::class, 'processEventApproval']);
    Route::get('/owner/merch-sales', [OwnerMerchController::class, 'index']);
    Route::get('/owner/merch-sales-summary', [OwnerMerchController::class, 'getMerchSalesSummary']);
    // RUTE UTAMA & RUTE SUMMARY REKAP TIKET OWNER (Sudah dipisah aman 🚀)
    Route::get('/owner/ticket-sales', [OwnerTicketController::class, 'getTicketSalesData']);
    Route::get('/owner/ticket-sales-summary', [OwnerTicketController::class, 'getTicketSalesSummary']);
    
    Route::get('/owner/wallet-ledgers', [OwnerWalletController::class, 'getWalletLedgers']);
    Route::put('/owner/withdrawals/{id}/status', [OwnerWalletController::class, 'updateWithdrawalStatus']);
    Route::get('/owner/history', [OwnerDashboardController::class, 'getOwnerHistory']);
    Route::get('/owner/platform-revenue/detail', [OwnerDashboardController::class, 'getPlatformRevenueDetail']);

    Route::get('/admin/dashboard', [AdminDashboardController::class, 'index']);
    Route::get('/admin/platform-wallet', [AdminPlatformWalletController::class, 'index']);
    Route::get('/admin/revenue/detail', [App\Http\Controllers\Api\AdminRevenueController::class, 'getRevenueDetail']);

    
    // Rute untuk mengambil semua daftar customer (GET /api/admin/users)
    Route::get('/admin/users', [AdminCustomerManagementController::class, 'getCustomerList']);
    
    // Rute untuk mengambil detail aktivitas customer berdasarkan ID (GET /api/admin/users/{id}/activity)
    Route::get('/admin/users/{id}/activity', [AdminCustomerManagementController::class, 'getCustomerActivity']);
    Route::get('/eo/dashboard', [EODashboardController::class, 'getDashboardData']);
    Route::get('/eo/active-events', [EODashboardController::class, 'getActiveEvents']);
    /// ADMIN EVENT MANAGEMENT (DI DALAM AUTH SANCTUM 🔒)
    Route::prefix('admin/events')->group(function () {
        Route::get('/', [AdminEventController::class, 'index']);
        Route::post('{id}/approve', [AdminEventController::class, 'approve']);
    });
    
});