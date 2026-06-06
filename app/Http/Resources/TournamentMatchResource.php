<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TournamentMatchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'match_id' => $this->match_id,
            'round' => $this->round,
            'position' => $this->position,
            'team1_player1_id' => $this->team1_player1_id,
            'team1_player2_id' => $this->team1_player2_id,
            'team2_player1_id' => $this->team2_player1_id,
            'team2_player2_id' => $this->team2_player2_id,
            'team1_player1' => new UserResource($this->whenLoaded('team1Player1')),
            'team1_player2' => new UserResource($this->whenLoaded('team1Player2')),
            'team2_player1' => new UserResource($this->whenLoaded('team2Player1')),
            'team2_player2' => new UserResource($this->whenLoaded('team2Player2')),
            'team1_score' => $this->team1_score,
            'team2_score' => $this->team2_score,
            'winner_id' => $this->winner_id,
            'winner' => new UserResource($this->whenLoaded('winner')),
            'status' => $this->status,
            'scheduled_at' => $this->scheduled_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
