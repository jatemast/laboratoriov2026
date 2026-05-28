<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $table = 'tournament_matches';

    protected $fillable = [
        'tournament_id',
        'match_id',
        'round',
        'position',
        'team1_player1_id',
        'team1_player2_id',
        'team2_player1_id',
        'team2_player2_id',
        'team1_score',
        'team2_score',
        'winner_id',
        'status',
        'scheduled_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function team1Player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team1_player1_id');
    }

    public function team1Player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team1_player2_id');
    }

    public function team2Player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team2_player1_id');
    }

    public function team2Player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team2_player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}
