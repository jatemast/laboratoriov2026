<?php

namespace App\Services;

use App\Models\User;

class PlayerSearchService
{
    /**
     * Buscar jugadores con filtros.
     */
    public function search(array $filters = [])
    {
        $query = User::with('profile')
            ->where('is_active', true);

        if (!empty($filters['name'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['name']}%")
                  ->orWhere('last_name', 'like', "%{$filters['name']}%");
            });
        }

        if (!empty($filters['skill_level'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('skill_level', $filters['skill_level']);
            });
        }

        if (!empty($filters['city'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('city', $filters['city']);
            });
        }

        if (!empty($filters['preferred_hand'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('preferred_hand', $filters['preferred_hand']);
            });
        }

        if (!empty($filters['gender'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('gender', $filters['gender']);
            });
        }

        return $query->orderBy('name')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Obtener perfil público de un jugador.
     */
    public function getPlayerProfile(int $userId): ?User
    {
        return User::with(['profile', 'reviews' => function ($q) {
            $q->latest()->limit(5);
        }])->findOrFail($userId);
    }

    /**
     * Obtener estadísticas de un jugador.
     */
    public function getPlayerStats(int $userId): array
    {
        $totalMatches = \App\Models\GameMatch::whereHas('players', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->count();

        $wins = \App\Models\GameMatch::where(function ($q) use ($userId) {
            $q->where('winner_team', 1)
              ->whereHas('players', function ($q2) use ($userId) {
                  $q2->where('user_id', $userId)->where('team', 1);
              });
        })->orWhere(function ($q) use ($userId) {
            $q->where('winner_team', 2)
              ->whereHas('players', function ($q2) use ($userId) {
                  $q2->where('user_id', $userId)->where('team', 2);
              });
        })->count();

        $losses = $totalMatches - $wins;
        $winRate = $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0;

        $recentMatches = \App\Models\GameMatch::whereHas('players', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['club', 'court'])
            ->latest()
            ->limit(10)
            ->get();

        return [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'losses' => $losses,
            'win_rate' => $winRate,
            'recent_matches' => $recentMatches,
        ];
    }
}
