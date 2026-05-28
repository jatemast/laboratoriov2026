<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tournament extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'club_id',
        'creator_id',
        'name',
        'description',
        'sport_type',
        'tournament_type',
        'match_type',
        'max_teams',
        'min_players_per_team',
        'max_players_per_team',
        'skill_level_min',
        'skill_level_max',
        'start_date',
        'end_date',
        'entry_fee',
        'prize',
        'status',
        'bracket_data',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'entry_fee' => 'decimal:2',
        'bracket_data' => 'array',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function players(): HasMany
    {
        return $this->hasMany(TournamentPlayer::class);
    }

    public function tournamentMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByClub($query, int $clubId)
    {
        return $query->where('club_id', $clubId);
    }
}
