<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('club_id')->constrained()->onDelete('cascade');
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sport_type')->default('padel');
            $table->string('tournament_type')->default('elimination'); // elimination, round_robin, league
            $table->string('match_type')->default('doubles'); // singles, doubles
            $table->integer('max_teams')->default(8);
            $table->integer('min_players_per_team')->default(2);
            $table->integer('max_players_per_team')->default(2);
            $table->string('skill_level_min')->nullable();
            $table->string('skill_level_max')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->string('prize')->nullable();
            $table->string('status')->default('registration'); // registration, in_progress, completed, cancelled
            $table->string('bracket_data')->nullable(); // JSON para almacenar el bracket
            $table->timestamps();
            $table->softDeletes();

            $table->index(['club_id', 'status']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
