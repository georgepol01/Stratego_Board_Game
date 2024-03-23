<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Define a route for the game page with dynamic parameters for the game ID and user ID
Route::get('/game/{gameId}/{playerId}', function ($gameId, $playerId) {
    return view('game', ['gameId' => $gameId, 'playerId' => $playerId]);
});
