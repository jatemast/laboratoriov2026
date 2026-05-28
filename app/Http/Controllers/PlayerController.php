<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Services\PlayerSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function __construct(
        protected PlayerSearchService $playerSearchService
    ) {}

    /**
     * Buscar jugadores.
     */
    public function search(Request $request): JsonResponse
    {
        $players = $this->playerSearchService->search($request->all());

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($players),
            'meta' => [
                'current_page' => $players->currentPage(),
                'last_page' => $players->lastPage(),
                'per_page' => $players->perPage(),
                'total' => $players->total(),
            ],
        ]);
    }

    /**
     * Obtener perfil público de un jugador.
     */
    public function show(int $id): JsonResponse
    {
        $player = $this->playerSearchService->getPlayerProfile($id);

        return response()->json([
            'success' => true,
            'data' => new UserResource($player),
        ]);
    }

    /**
     * Obtener estadísticas de un jugador.
     */
    public function stats(int $id): JsonResponse
    {
        $stats = $this->playerSearchService->getPlayerStats($id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
