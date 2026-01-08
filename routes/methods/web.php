<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});
Route::get('/lang/{locale}', function ($locale) {
    if (!in_array($locale, ['uz', 'ru', 'en', 'ko', 'zh']
)) {
        abort(400);
    }

    session(['locale' => $locale]);

    return redirect()->back();
})->name('lang.switch');
