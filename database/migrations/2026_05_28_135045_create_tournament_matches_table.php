<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->onDelete('cascade');
            $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('set null');
            $table->integer('round')->default(1);
            $table->integer('position')->default(1); // posición en el bracket
            $table->foreignId('team1_player1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team1_player2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team2_player1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('team2_player2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('team1_score')->nullable();
            $table->integer('team2_score')->nullable();
            $table->foreignId('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, scheduled, in_progress, completed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'round']);
            $table->index(['tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
