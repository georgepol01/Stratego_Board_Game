<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });



//Start game
Route::post('/game/start', [GameController::class, 'startGame']);

//Create pieces in db
Route::post('/game/init-board/{gameId}', [GameController::class, 'createInitialBoard']);

// Update game pieces
Route::post('/game/update-game-pieces/{gameId}', [GameController::class, 'updateGamePieces']);

// Update game status turn
Route::post('/game/set-game-turn/{gameTurn}', [GameController::class, 'setGameTurn']);

// Clear game
Route::post('/game/clear-game/{gameId}', [GameController::class, 'clearGame']);




//Check status to start game
Route::get('/game/status/{gameId}', [GameController::class, 'getGameStatus']);

//Create game board
Route::get('/game/create-game-board/{gameId}', [GameController::class, 'getGameBoard']);

//Get player color
Route::get('/game/get-player-color/{playerId}', [GameController::class, 'getPlayerColor']);

//Get players ids
Route::get('/game/get-players/{gameId}', [GameController::class, 'getPlayers']);

//Get game time
Route::get('/game/get-game-time/{gameId}', [GameController::class, 'getGameTime']);

//Get game turn
Route::get('/game/get-game-turn/{gameId}', [GameController::class, 'getGameTurn']);
