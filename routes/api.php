<?php


use App\Models\Product;
use Illuminate\Http\Request;
use PHPUnit\Event\Code\Test;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;



Route::get('/user', function (Request $request) {
    return UserResource::make($request->user());
 })->middleware('auth:sanctum');



Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

