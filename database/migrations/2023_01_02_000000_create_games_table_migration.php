<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player1_id')->nullable();
            $table->unsignedBigInteger('player2_id')->nullable();
            $table->enum('status', ['waiting', 'playing', 'finished']);
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->unsignedBigInteger('turn_player_id')->nullable();
            $table->timestamps();

            $table->foreign('player1_id')->references('id')->on('players');
            $table->foreign('player2_id')->references('id')->on('players');
            $table->foreign('winner_id')->references('id')->on('players');
            $table->foreign('turn_player_id')->references('id')->on('players');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
