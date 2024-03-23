<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    public $timestamps = false;

    protected $fillable = ['username', 'color'];

    public function games()
    {
        return $this->belongsToMany(Game::class);
    }
}