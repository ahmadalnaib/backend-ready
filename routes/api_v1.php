<?php


use App\Models\Product;
use Illuminate\Http\Request;
use PHPUnit\Event\Code\Test;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProductController;



Route::prefix('v1')->group(function () {
    Route::apiResource('products', ProductController::class)->only(['index', 'store', 'show', 'update']);
});



Route::get('/user', function (Request $request) {
    return UserResource::make($request->user());
})->middleware('auth:sanctum');
