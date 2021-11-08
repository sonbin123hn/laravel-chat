<?php

use Illuminate\Support\Facades\Route;

Route::post('/login', ['App\Containers\User\API\V1\Controllers\Auth\APIController', 'login'])->name('login');

Route::middleware('auth.jwt')->group(function () {
    Route::get('logout', ['App\Containers\User\API\V1\Controllers\Auth\APIController', 'logout']);
    Route::get('users', ['App\Containers\User\API\V1\Controllers\Auth\UserController', 'index']);
});
