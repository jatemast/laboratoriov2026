<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'sender_id' => $this->sender_id,
            'receiver_id' => $this->receiver_id,
            'match_id' => $this->match_id,
            'message' => $this->message,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'sender' => new UserResource($this->whenLoaded('sender')),
            'receiver' => new UserResource($this->whenLoaded('receiver')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
