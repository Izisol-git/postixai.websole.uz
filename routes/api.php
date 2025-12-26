<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Bot\TelegramBotController;



Route::post('/telegram/webhook', [TelegramBotController::class, 'webhook']);
Route::post('/login', [AuthController::class, 'login'])->name('api.login');


Route::middleware(['auth:sanctum'])->group(function () {

    Route::middleware(['role:admin,superadmin'])->group(function () {
        // Route::apiResource('users', UserController::class);
        // Route::post('telegram/login', [\App\Http\Controllers\Api\TelegramController::class, 'login'])->name('telegram.login');
        // Route::post('telegram/verify', [\App\Http\Controllers\Api\TelegramController::class, 'verify'])->name('telegram.verify');
        // Route::post('telegram/logout', [\App\Http\Controllers\Api\TelegramController::class, 'logout'])->name('telegram.logout');
        // Route::get('history', [\App\Http\Controllers\Api\HistoryController::class, 'index'])->name('api.history');
    });
    Route::middleware(['role:superadmin'])->group(function () {
        Route::apiResource('departments', DepartmentController::class)->names([
            'index'   => 'api.departments.index',
            'store'   => 'api.departments.store',
            'show'    => 'api.departments.show',
            'update'  => 'api.departments.update',
            'destroy' => 'api.departments.destroy',
        ]);
    });
});
