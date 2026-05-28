<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TournamentPlayerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'tournament_id' => $this->tournament_id,
            'user_id' => $this->user_id,
            'team_name' => $this->team_name,
            'status' => $this->status,
            'seed' => $this->seed,
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
