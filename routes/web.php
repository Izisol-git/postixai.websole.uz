<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\View\AuthController;
use App\Http\Controllers\View\UserController;
use App\Http\Controllers\View\TelegramController;
use App\Http\Controllers\View\DepartmentController;

Route::get('/', function () {
    return view('auth.login');
});


Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'authenticate']);

Route::middleware('auth')->group(function () {


    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


    Route::get('departments/create', [DepartmentController::class, 'create'])->name('departments.create');
    Route::post('departments', [DepartmentController::class, 'store'])->name('departments.store');
    Route::get('departments/{department}', [DepartmentController::class, 'show'])->name('departments.show');
    Route::get('departments/{department}/edit', [DepartmentController::class, 'edit'])->name('departments.edit');
    Route::put('departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
    Route::delete('departments/{department}', [DepartmentController::class, 'destroy'])->name('departments.destroy');

    Route::middleware('role:superadmin')->group(function () {
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');

    });
    Route::middleware('role:superadmin,admin')->group(function () {
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    
    Route::get('phones/',[TelegramController::class,'showLoginForm'])->name('telegram.login');
    Route::post('phones/send',[TelegramController::class,'sendPhone'])->name('telegram.sendPhone');
    Route::post('phones/verify',[TelegramController::class,'sendCode'])->name('telegram.sendCode');

});
});
