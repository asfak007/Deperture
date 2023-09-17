<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\Api\V1\FrontendController;
use App\Http\Controllers\Api\V1\Auth\AgencyController;
use App\Http\Controllers\Api\V1\Auth\CustomerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// V1 Base Route.
Route::any('/', [FrontendController::class, "index"])->name("index");

// Guest routes
Route::group(['prefix' => 'auth', "middleware" => "guest"], function () {

    // auth default route
    Route::get('/', [FrontendController::class, "auth"])->name("auth");


    // Customer Routes
    Route::group(['prefix' => 'customer', 'as' => 'customer.', "controller" => CustomerController::class], function () {
        // guest route
        Route::middleware(['customer:false'])->group(function () {
            Route::post('/login', 'login')->name("login");
            Route::post('/register', 'register')->name("register");
        });

        // authorization route
        Route::middleware(['customer'])->group(function () {
            Route::get('/', 'customer')->name("customer");
            Route::post('/update', 'update')->name("update");
            Route::post('/update/password', 'password')->name("password");
            Route::post('/deactivate', 'deactivate')->name("deactivate");
            Route::post('/logout', 'logout')->name("logout");

            Route::post('/wishList/add',[\App\Http\Controllers\Api\wishlistController::class,'add']);
        });
    });

    // Agency Routes
    Route::group(['prefix' => 'agency', 'as' => 'agency.', "controller" => AgencyController::class], function () {
        // guest route
        Route::middleware(['agency:false'])->group(function () {
            Route::post('/login', 'login')->name("login");
            Route::post('/register', 'register')->name("register");
        });

        // authorization route
        Route::middleware(['agency'])->group(function () {
            Route::get('/', 'agency')->name("agency");
            Route::post('/update', 'update')->name("update");
            Route::post('/update/password', 'password')->name("password");
            Route::post('/deactivate', 'deactivate')->name("deactivate");
            Route::post('/logout', 'logout')->name("logout");
        });
    });
});


// Services routes
Route::resource('service', ServiceController::class)->only(["index"]);
Route::group(["as" => "service.", 'prefix' => 'service', "controller" => ServiceController::class], function () {
    Route::get('/details/{service}', 'details')->name("details");
    Route::get('/popular', 'popular')->name("popular");
    Route::get('/recommended', 'recommended')->name("recommended");
    // Route::post('/register', 'register')->name("register");
    // Route::post('/update', 'update')->name("update");
    // Route::post('/review/{service}', 'review')->middleware("customer")->name("review");
});


// Categories routes
Route::group(["as" => "categories.", "controller" => CategoryController::class], function () {
    Route::resource('categories', CategoryController::class)->only(['index', 'show']);
});

