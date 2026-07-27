<?php

use App\Http\Controllers\ContentController;
use App\Http\Controllers\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('content/search', [ContentController::class, 'search']);
Route::get('projects', [ProjectController::class, 'index']);
