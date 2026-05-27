<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'avatar_url',
        'bio',
        'gender',
        'birth_date',
        'skill_level',
        'preferred_hand',
        'preferences',
        'availability',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'preferences' => 'array',
        'availability' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
