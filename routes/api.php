<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GameController;
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
Route::post('register', [UserController::class, 'register']); // Create a new player
Route::post('login', [UserController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    
    Route::middleware('role:player')->group(function () { // player allowed methods
        Route::put('/players/{id}', [UserController::class, 'update']); // Update player nickname
        Route::post('/players/{id}/games/', [GameController::class, 'throwDice']); // Player makes a roll
        Route::delete('/players/{id}/games', [GameController::class, 'destroy']); // Delete all moves for player id.
        Route::get('/players/{id}/games', [GameController::class, 'listGames']); // List of plays for player and % of success.
    });

    Route::middleware('role:admin')->group(function () { // adnministrator allowed methods 
        Route::get('players', [UserController::class, 'listPlayers']); // All players
        Route::get('/players/ranking', [UserController::class, 'ranking']); // Average success rate
        Route::get('/players/ranking/loser', [UserController::class, 'loser']); // Player with the lowest success rate
        Route::get('/players/ranking/winner', [UserController::class, 'winner']); // Player with the highest success rate
    });

    Route::post('logout', [UserController::class, 'logout']);
});

Route::fallback(function () {
    return response()->json(['message' => 'Log in again, please.'], 401);
});