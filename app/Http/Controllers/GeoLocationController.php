<?php

namespace App\Http\Controllers;

use App\Http\Resources\ClubResource;
use App\Http\Resources\UserResource;
use App\Services\GeoLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeoLocationController extends Controller
{
    public function __construct(
        protected GeoLocationService $geoLocationService
    ) {}

    /**
     * Obtener clubs cercanos.
     */
    public function nearbyClubs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
            'sport_type' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
        ]);

        $clubs = $this->geoLocationService->nearbyClubs(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? 10,
            $request->only(['sport_type', 'search'])
        );

        return response()->json([
            'success' => true,
            'data' => ClubResource::collection($clubs),
        ]);
    }

    /**
     * Obtener jugadores cercanos.
     */
    public function nearbyPlayers(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
            'skill_level' => 'nullable|string|max:50',
            'search' => 'nullable|string|max:255',
        ]);

        $players = $this->geoLocationService->nearbyPlayers(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius'] ?? 10,
            $request->only(['skill_level', 'search'])
        );

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($players),
        ]);
    }

    /**
     * Calcular distancia entre dos puntos.
     */
    public function calculateDistance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lat1' => 'required|numeric|between:-90,90',
            'lng1' => 'required|numeric|between:-180,180',
            'lat2' => 'required|numeric|between:-90,90',
            'lng2' => 'required|numeric|between:-180,180',
        ]);

        $distance = $this->geoLocationService->calculateDistance(
            $validated['lat1'],
            $validated['lng1'],
            $validated['lat2'],
            $validated['lng2']
        );

        return response()->json([
            'success' => true,
            'data' => ['distance_km' => $distance],
        ]);
    }
}
