<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HomeApiController;
use App\Http\Controllers\Api\TicketController;

Route::post('/xendit/webhook', [WebhookController::class, 'handleCallback']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);
Route::post('/checkout', [TicketController::class, 'checkout']);
Route::get('/home', [HomeApiController::class, 'index']);
