<?php

namespace App\Services;

use App\Models\ActivityFeed;
use App\Models\GameMatch;
use App\Models\Review;
use App\Models\Booking;

class FeedService
{
    /**
     * Obtener feed de actividad para un usuario.
     */
    public function getFeed(int $userId, array $filters = [])
    {
        $query = ActivityFeed::with('user.profile')
            ->where('user_id', $userId);

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Obtener feed global (actividad de todos los usuarios).
     */
    public function getGlobalFeed(array $filters = [])
    {
        $query = ActivityFeed::with('user.profile');

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Crear una entrada en el feed.
     */
    public function create(int $userId, string $type, string $message, ?array $data = null): ActivityFeed
    {
        return ActivityFeed::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Crear entrada cuando se juega un partido.
     */
    public function matchPlayed(GameMatch $match): void
    {
        foreach ($match->users as $user) {
            $this->create(
                $user->id,
                'match_played',
                "Jugó un partido de {$match->match_type}",
                ['match_id' => $match->id, 'club_id' => $match->club_id]
            );
        }
    }

    /**
     * Crear entrada cuando se crea un partido.
     */
    public function matchCreated(GameMatch $match, int $userId): void
    {
        $this->create(
            $userId,
            'match_created',
            "Creó un nuevo partido de {$match->match_type}",
            ['match_id' => $match->id, 'club_id' => $match->club_id]
        );
    }

    /**
     * Crear entrada cuando se hace una reseña.
     */
    public function reviewCreated(Review $review): void
    {
        $this->create(
            $review->user_id,
            'review',
            "Dejó una reseña de {$review->rating} estrellas",
            ['review_id' => $review->id, 'reviewable_type' => $review->reviewable_type]
        );
    }

    /**
     * Crear entrada cuando se confirma una reserva.
     */
    public function bookingConfirmed(Booking $booking): void
    {
        $this->create(
            $booking->user_id,
            'booking',
            "Reservó una cancha para el {$booking->booking_date}",
            ['booking_id' => $booking->id, 'club_id' => $booking->club_id]
        );
    }
}
