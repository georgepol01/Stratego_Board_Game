<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    protected $fillable = ['player1_id', 'player2_id', 'status', 'winner_id', 'turn_player_id'];

    public function player1()
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2()
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function winner()
    {
        return $this->belongsTo(Player::class, 'winner_id');
    }

    public function pieces()
    {
        return $this->hasMany(Piece::class);
    }

    public function players()
    {
        // Specify the pivot table and foreign keys
        return $this->belongsToMany(Player::class, 'game_player', 'game_id', 'player_id')
            ->withTimestamps();
    }
}