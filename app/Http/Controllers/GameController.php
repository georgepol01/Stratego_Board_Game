<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Piece;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class GameController extends Controller
{
    public function startGame(Request $request)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            // Create a new user with the provided name
            $player = Player::create([
                'username' => $validatedData['name'],
            ]);

            // Find an open game or create a new one
            $game = Game::where('status', 'waiting')->first();

            if (!$game) {
                // Create a new game if no open game is found
                $game = Game::create(['status' => 'waiting']);
            }

            // Attach the user to the game
            $game->players()->attach($player);

            // Refresh the game model to get the updated relationships
            $game->refresh();

            // Check if there are now two players in the game
            if ($game->players->count() === 2) {
                // Assign different colors to players in the same game
                $colors = ['red', 'blue'];

                // Randomly select indices
                $indexPlayer1 = array_rand($colors);
                $indexPlayer2 = array_rand($colors);

                // Ensure players have different colors
                while ($indexPlayer2 == $indexPlayer1) {
                    $indexPlayer2 = array_rand($colors);
                }

                // Randomly select a player as the turn player
                $turnPlayerIndex = array_rand($game->players->toArray());
                $turnPlayerId = $game->players[$turnPlayerIndex]->id;

                // Update player colors and turn_player_id in the games table
                $game->players[0]->update(['color' => $colors[$indexPlayer1]]);
                $game->players[1]->update(['color' => $colors[$indexPlayer2]]);
                $game->update(['status' => 'playing', 'player1_id' => $game->players[0]->id, 'player2_id' => $game->players[1]->id, 'turn_player_id' => $turnPlayerId]);

                // Return the game ID and user ID in the response
                return response()->json([
                    'gameId' => $game->id,
                    'playerId' => $player->id,
                    'status' => $game->status,
                ]);
            }

            // Assuming the game ID is sent back to the client along with the updated status
            return response()->json(['gameId' => $game->id, 'playerId' => $player->id, 'status' => $game->status]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error starting the game', 'message' => $e->getMessage()], 500);
        }
    }

    public function getGameStatus($gameId)
    {
        try {
            // Find the game by ID
            $game = Game::findOrFail($gameId);

            // Assuming the response includes the game status
            return response()->json(['status' => $game->status]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching game status', 'message' => $e->getMessage()], 500);
        }
    }

    public function getGameTime($gameId)
    {
        try {
            // Find the game by ID
            $game = Game::findOrFail($gameId);

            // Get the created_at timestamp
            $createdAt = $game->created_at;

            // Get the current server time
            $serverTime = Carbon::now();

            // Return both timestamps
            return response()->json(['gameTime' => $createdAt, 'serverTime' => $serverTime]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching game time', 'message' => $e->getMessage()], 500);
        }
    }

    public function getGameTurn($gameId)
    {
        try {
            // Find the game by ID
            $game = Game::findOrFail($gameId);

            // Check if the game status is 'finished'
            if ($game->status === 'finished') {
                return response()->json(['gameTurn' => null, 'gameFinished' => true]);
            }

            // Get the turn player ID
            $gameTurn = $game->turn_player_id;

            // Return the turn player ID and indicate that the game is not finished
            return response()->json(['gameTurn' => $gameTurn, 'gameFinished' => false]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching game turn', 'message' => $e->getMessage()], 500);
        }
    }


    public function getPlayerColor($playerId)
    {
        try {
            // Fetch the player color from the 'players' table
            $playerColor = Player::where('id', $playerId)->value('color');

            // Assuming the response includes the player color
            return response()->json(['color' => $playerColor]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching player color', 'message' => $e->getMessage()], 500);
        }
    }

    public function getGameBoard($gameId)
    {
        try {
            // Fetch the initial game board pieces
            $initialPieces = Piece::where('game_id', $gameId)->get();

            // Assuming the response includes the initial board pieces
            return response()->json($initialPieces);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching initial game board', 'message' => $e->getMessage()], 500);
        }
    }

    public function getPlayers($gameId)
    {
        try {
            // Fetch player IDs from the game
            $game = Game::find($gameId);
            $player1Id = $game->player1_id;
            $player2Id = $game->player2_id;

            return response()->json(['player1Id' => $player1Id, 'player2Id' => $player2Id]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching players ids', 'message' => $e->getMessage()], 500);
        }
    }

    public function createInitialBoard($gameId)
    {
        // Check if the game board already has pieces
        if (Piece::where('game_id', $gameId)->exists()) {
            return response()->json(['message' => 'Board already initialized'], 200);
        }

        // Use a transaction to ensure atomicity
        DB::beginTransaction();

        try {

            $playerPieces = [
                ['type' => 'bomb', 'quantity' => 6],
                ['type' => 'marshal', 'quantity' => 1],
                ['type' => 'general', 'quantity' => 1],
                ['type' => 'colonel', 'quantity' => 2],
                ['type' => 'major', 'quantity' => 3],
                ['type' => 'captain', 'quantity' => 4],
                ['type' => 'lieutenant', 'quantity' => 4],
                ['type' => 'sergeant', 'quantity' => 4],
                ['type' => 'miner', 'quantity' => 5],
                ['type' => 'scout', 'quantity' => 8],
                ['type' => 'spy', 'quantity' => 1],
                ['type' => 'flag', 'quantity' => 1],
            ];

            // Fetch player IDs from the game
            $game = Game::find($gameId);
            $player1Id = $game->player1_id;
            $player2Id = $game->player2_id;

            // Fetch player colors based on their IDs
            $player1color = Player::find($player1Id)->color;
            $player2color = Player::find($player2Id)->color;

            $positionX = 1;
            $positionY = 1;

            foreach ($playerPieces as $piece) {

                for ($i = 0; $i < $piece['quantity']; $i++) {

                    $newPiece = new Piece([
                        'type' => $piece['type'],
                        'position_x' => $positionX,
                        'position_y' => $positionY,
                        'game_id' => $gameId,
                        'player_id' => $player1Id,
                        'color' => $player1color,
                    ]);

                    $newPiece->save();

                    $positionX++;
                    if ($positionX > 10) {
                        $positionX = 1;
                        $positionY++;
                    }
                }
            }

            $positionX = 1;
            $positionY = 7;

            foreach ($playerPieces as $piece) {

                for ($i = 0; $i < $piece['quantity']; $i++) {

                    $newPiece = new Piece([
                        'type' => $piece['type'],
                        'position_x' => $positionX,
                        'position_y' => $positionY,
                        'game_id' => $gameId,
                        'player_id' => $player2Id,
                        'color' => $player2color,
                    ]);

                    $newPiece->save();

                    $positionX++;
                    if ($positionX > 10) {
                        $positionX = 1;
                        $positionY++;
                    }
                }
            }

            DB::commit();

            // Return the created initial board
            return response()->json(['message' => 'Initial board created successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => 'Error creating initial board', 'message' => $e->getMessage()], 500);
        }
    }

    public function updateGamePieces(Request $request, $gameId)
    {
        try {
            // Update the positions of pieces in the database
            $updatedPieces = $request->input('pieces');

            // Use a transaction to ensure atomicity
            DB::beginTransaction();

            foreach ($updatedPieces as $updatedPiece) {
                if (!isset($updatedPiece['id'])) {
                    // If the 'id' is not set, return an error response
                    return response()->json(['error' => 'Missing piece id'], 400);
                }
                // Assuming each piece has an 'id' field
                $piece = Piece::find($updatedPiece['id']);

                if ($piece) {
                    // Update the 'position_x' and 'position_y' fields
                    $piece->position_x = $updatedPiece['position_x'];
                    $piece->position_y = $updatedPiece['position_y'];

                    // Check if position_x and position_y are zero, set the piece status to captured
                    if ($updatedPiece['position_x'] == 0 && $updatedPiece['position_y'] == 0) {
                        $piece->status = 'captured';
                    }

                    $piece->save();
                }
            }

            // Fetch the updated pieces from the database
            $game = Game::find($gameId);
            $updatedPieces = $game->pieces;

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Game pieces updated successfully', 'updatedPieces' => $updatedPieces]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollback();

            return response()->json(['error' => 'Error updating game pieces', 'message' => $e->getMessage()], 500);
        }
    }

    public function setGameTurn(Request $request, $gameId)
    {
        try {
            $newGameTurn = $request->input('gameTurn');

            // Use a transaction to ensure atomicity
            DB::beginTransaction();

            // Update the 'turn_player_id' in the 'games' table
            $game = Game::find($gameId);
            $game->turn_player_id = $newGameTurn;
            $game->save();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Game turn updated successfully', 'newGameTurn' => $newGameTurn]);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollback();

            return response()->json(['error' => 'Error updating game turn', 'message' => $e->getMessage()], 500);
        }
    }

    public function clearGame($gameId)
    {
        try {
            // Use a transaction to ensure atomicity
            DB::beginTransaction();

            // Update the 'winner_id' and 'status' in the 'games' table
            $game = Game::find($gameId);
            $game->winner_id = $game->turn_player_id;
            $game->status = 'finished';
            $game->save();

            // Delete all pieces associated with the game
            Piece::where('game_id', $gameId)->delete();

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Game cleared successfully']);
        } catch (\Exception $e) {
            // Rollback the transaction in case of an error
            DB::rollback();

            return response()->json(['error' => 'Error clearing game', 'message' => $e->getMessage()], 500);
        }
    }
}
