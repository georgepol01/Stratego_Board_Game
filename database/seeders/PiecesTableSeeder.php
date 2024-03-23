<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PiecesTableSeeder extends Seeder
{
    public function run()
    {
        // Add sample data for pieces
        $pieces = [];

        // Player 1 pieces
        $player1Pieces = $this->generatePlayerPieces(1, 'blue');
        $pieces = array_merge($pieces, $player1Pieces);

        // Player 2 pieces
        $player2Pieces = $this->generatePlayerPieces(2, 'red');
        $pieces = array_merge($pieces, $player2Pieces);

        // Insert into the database
        DB::table('pieces')->insert($pieces);
    }

    private function generatePlayerPieces($playerNumber, $color)
    {
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

        $pieces = [];
        $positionX = 1;
        $positionY = ($playerNumber == 1) ? 1 : 7; // Set initial positionY based on playerNumber

        foreach ($playerPieces as $piece) {
            for ($i = 0; $i < $piece['quantity']; $i++) {
                $pieces[] = [
                    'game_id' => 1, // Adjust game_id as needed
                    'user_id' => $playerNumber, // Player 1 or 2
                    'type' => $piece['type'],
                    'position_x' => $positionX,
                    'position_y' => $positionY,
                    'status' => 'active',
                ];

                $positionX++;
                if ($positionX > 10) {
                    $positionX = 1;
                    $positionY++;
                }
            }
        }

        return $pieces;
    }
}
