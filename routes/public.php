<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::get('/user/email', [UserController::class, 'findEmail']);
Route::post('/user/create', [UserController::class, 'create']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
