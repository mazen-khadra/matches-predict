<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth as AuthController;
use App\Http\Controllers\MatchPredict as MatchPredictController;
use App\Http\Controllers\Matches as MatchController;
use App\Http\Controllers\User as UserController;
use App\Http\Controllers\Img as ImgController;

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

Route::prefix('auth')->group(function () {
    Route::post('signup', [AuthController::class, "signUp"]);
    Route::post('login', [AuthController::class, "login"]);
    Route::post('logout', [AuthController::class, "logOut"])->middleware('auth:sanctum');
    Route::get('user', [AuthController::class, "getLoggedInUser"])->middleware('auth:sanctum');
});


Route::prefix('predict')->middleware('auth:sanctum')->group(function () {
    Route::get('/list', [MatchPredictController::class, "index"]);
    Route::post('/', [MatchPredictController::class, "add"]);
    Route::get('/stats/match/{matchId}', [MatchPredictController::class, "getMatchStats"]);
});

Route::prefix('match')->middleware('auth:sanctum')->group(function() {
    Route::get('/list', [MatchController::class, "index"]);
    Route::get('/{matchId}/details', [MatchController::class, "details"]);
});

Route::prefix('users')->middleware('auth:sanctum')->group(function() {
    Route::get('/list', [UserController::class, "index"]);
    Route::post('/profile', [UserController::class, 'updateProfile']);
});

Route::prefix('resource')->group(function() {
    Route::post('/img', [ImgController::class, 'uploadImg']);
});
