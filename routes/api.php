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
use App\Http\Controllers\Api\EOWithdrawalController;
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

/// HOME
Route::get(
    '/home',
    [HomeApiController::class, 'index']
);

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
    Route::get('/ticket-sales/{id}',[EODashboardController::class, 'ticketSalesDetail']);
    Route::get(
        '/eo/{eoId}/event-wallets',
        [EOWithdrawalController::class, 'eventWallets']
    );
});