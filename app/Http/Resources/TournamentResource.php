<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TournamentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'club_id' => $this->club_id,
            'creator_id' => $this->creator_id,
            'name' => $this->name,
            'description' => $this->description,
            'sport_type' => $this->sport_type,
            'tournament_type' => $this->tournament_type,
            'match_type' => $this->match_type,
            'max_teams' => $this->max_teams,
            'min_players_per_team' => $this->min_players_per_team,
            'max_players_per_team' => $this->max_players_per_team,
            'skill_level_min' => $this->skill_level_min,
            'skill_level_max' => $this->skill_level_max,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'entry_fee' => (float) $this->entry_fee,
            'prize' => $this->prize,
            'status' => $this->status,
            'players_count' => $this->players_count ?? ($this->relationLoaded('players') ? $this->players->count() : 0),
            'club' => new ClubResource($this->whenLoaded('club')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'players' => TournamentPlayerResource::collection($this->whenLoaded('players')),
            'tournament_matches' => TournamentMatchResource::collection($this->whenLoaded('tournamentMatches')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
