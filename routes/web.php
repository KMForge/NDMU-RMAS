<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/preview/student-dashboard', function () {
    return view('pages.student-dashboard');
});

require __DIR__.'/auth.php';
