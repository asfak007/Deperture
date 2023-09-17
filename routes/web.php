<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\FrontendController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [FrontendController::class, "index"])->name("root");
Route::get('/api', [FrontendController::class, "index"])->name("api.index");
Route::get('/v1', [FrontendController::class, "index"])->name("api.v1");
