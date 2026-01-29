<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\HomeApiController;

Route::post('/xendit/webhook', [WebhookController::class, 'handleCallback']);
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

Route::get('/home', [HomeApiController::class, 'index']);
