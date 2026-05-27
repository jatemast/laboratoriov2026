<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'avatar' => $this->avatar_url, // Mapear avatar_url como 'avatar' para compatibilidad
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'skill_level' => $this->skill_level,
            'preferred_hand' => $this->preferred_hand,
            'preferences' => $this->preferences,
            'availability' => $this->availability,
        ];
    }
}
