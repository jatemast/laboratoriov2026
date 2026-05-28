<?php

namespace App\Services;

use App\Models\Club;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GeoLocationService
{
    /**
     * Fórmula Haversine para calcular distancia entre coordenadas.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Obtener clubs cercanos a una ubicación.
     */
    public function nearbyClubs(float $latitude, float $longitude, float $radius = 10, array $filters = [])
    {
        // Usar aproximación con bounding box para eficiencia
        $latDelta = $radius / 111.32;
        $lngDelta = $radius / (111.32 * cos(deg2rad($latitude)));

        $query = Club::where('is_active', true)
            ->where('latitude', '>=', $latitude - $latDelta)
            ->where('latitude', '<=', $latitude + $latDelta)
            ->where('longitude', '>=', $longitude - $lngDelta)
            ->where('longitude', '<=', $longitude + $lngDelta)
            ->withCount('courts');

        if (!empty($filters['sport_type'])) {
            $query->whereHas('courts', function ($q) use ($filters) {
                $q->where('sport_type', $filters['sport_type']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('city', 'like', "%{$filters['search']}%")
                  ->orWhere('address', 'like', "%{$filters['search']}%");
            });
        }

        $clubs = $query->get();

        // Calcular distancia y ordenar
        $clubs = $clubs->map(function ($club) use ($latitude, $longitude) {
            $club->distance = round(
                $this->haversine($latitude, $longitude, $club->latitude, $club->longitude),
                2
            );
            return $club;
        })->sortBy('distance')->values();

        // Filtrar por radio exacto
        if ($radius > 0) {
            $clubs = $clubs->where('distance', '<=', $radius)->values();
        }

        return $clubs;
    }

    /**
     * Obtener jugadores cercanos (con perfil público y ubicación).
     */
    public function nearbyPlayers(float $latitude, float $longitude, float $radius = 10, array $filters = [])
    {
        $latDelta = $radius / 111.32;
        $lngDelta = $radius / (111.32 * cos(deg2rad($latitude)));

        $query = User::where('is_active', true)
            ->whereHas('profile', function ($q) {
                $q->whereNotNull('availability');
            });

        if (!empty($filters['skill_level'])) {
            $query->whereHas('profile', function ($q) use ($filters) {
                $q->where('skill_level', $filters['skill_level']);
            });
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('last_name', 'like', "%{$filters['search']}%");
            });
        }

        $users = $query->with('profile')->get();

        // Nota: La ubicación del usuario se almacena en profile->availability (JSON)
        // Esto es un placeholder - idealmente se necesita una tabla user_locations
        $users = $users->filter(function ($user) {
            $availability = $user->profile?->availability;
            return $availability && isset($availability['latitude']);
        })->map(function ($user) use ($latitude, $longitude) {
            $avail = $user->profile->availability;
            $user->distance = round(
                $this->haversine($latitude, $longitude, $avail['latitude'], $avail['longitude']),
                2
            );
            $user->latitude = $avail['latitude'];
            $user->longitude = $avail['longitude'];
            return $user;
        })->sortBy('distance')->values();

        if ($radius > 0) {
            $users = $users->where('distance', '<=', $radius)->values();
        }

        return $users;
    }

    /**
     * Calcular distancia entre dos puntos.
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        return round($this->haversine($lat1, $lng1, $lat2, $lng2), 2);
    }
}
