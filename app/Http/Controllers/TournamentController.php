<?php

namespace App\Http\Controllers;

use App\Http\Resources\TournamentResource;
use App\Http\Resources\TournamentPlayerResource;
use App\Services\TournamentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TournamentController extends Controller
{
    public function __construct(
        protected TournamentService $tournamentService
    ) {}

    /**
     * Listar torneos.
     */
    public function index(Request $request): JsonResponse
    {
        $tournaments = $this->tournamentService->list($request->all());

        return response()->json([
            'success' => true,
            'data' => TournamentResource::collection($tournaments),
            'meta' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
            ],
        ]);
    }

    /**
     * Crear un torneo.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'club_id' => 'required|exists:clubs,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'sport_type' => 'nullable|string|max:50',
            'tournament_type' => 'nullable|in:elimination,round_robin,league',
            'match_type' => 'nullable|in:singles,doubles',
            'max_teams' => 'nullable|integer|min:2|max:64',
            'min_players_per_team' => 'nullable|integer|min:1|max:10',
            'max_players_per_team' => 'nullable|integer|min:1|max:10',
            'skill_level_min' => 'nullable|string|max:50',
            'skill_level_max' => 'nullable|string|max:50',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'entry_fee' => 'nullable|numeric|min:0',
            'prize' => 'nullable|string|max:255',
            'team_name' => 'nullable|string|max:255',
        ]);

        $tournament = $this->tournamentService->create($validated, auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Torneo creado exitosamente.',
            'data' => new TournamentResource($tournament),
        ], 201);
    }

    /**
     * Mostrar un torneo.
     */
    public function show(int $id): JsonResponse
    {
        $tournament = $this->tournamentService->findById($id);

        return response()->json([
            'success' => true,
            'data' => new TournamentResource($tournament),
        ]);
    }

    /**
     * Inscribirse a un torneo.
     */
    public function register(Request $request, int $tournamentId): JsonResponse
    {
        $validated = $request->validate([
            'team_name' => 'nullable|string|max:255',
        ]);

        try {
            $player = $this->tournamentService->register(
                $tournamentId,
                auth()->id(),
                $validated['team_name'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Inscripción realizada exitosamente.',
                'data' => new TournamentPlayerResource($player),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Aprobar inscripción (solo admin del club).
     */
    public function approvePlayer(int $tournamentId, int $playerId): JsonResponse
    {
        $player = $this->tournamentService->updatePlayerStatus(
            $tournamentId,
            $playerId,
            'approved'
        );

        return response()->json([
            'success' => true,
            'message' => 'Jugador aprobado.',
            'data' => new TournamentPlayerResource($player),
        ]);
    }

    /**
     * Rechazar inscripción.
     */
    public function rejectPlayer(int $tournamentId, int $playerId): JsonResponse
    {
        $player = $this->tournamentService->updatePlayerStatus(
            $tournamentId,
            $playerId,
            'rejected'
        );

        return response()->json([
            'success' => true,
            'message' => 'Jugador rechazado.',
            'data' => new TournamentPlayerResource($player),
        ]);
    }

    /**
     * Iniciar torneo.
     */
    public function start(int $tournamentId): JsonResponse
    {
        try {
            $tournament = $this->tournamentService->startTournament($tournamentId);

            return response()->json([
                'success' => true,
                'message' => 'Torneo iniciado.',
                'data' => new TournamentResource($tournament),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Actualizar resultado de partido de torneo.
     */
    public function updateMatchResult(Request $request, int $matchId): JsonResponse
    {
        $validated = $request->validate([
            'team1_score' => 'required|integer|min:0',
            'team2_score' => 'required|integer|min:0',
            'winner_id' => 'required|exists:users,id',
        ]);

        $tournamentMatch = $this->tournamentService->updateMatchResult(
            $matchId,
            $validated['team1_score'],
            $validated['team2_score'],
            $validated['winner_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Resultado actualizado.',
            'data' => $tournamentMatch,
        ]);
    }

    /**
     * Eliminar un torneo.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->tournamentService->delete($id);

        return response()->json([
            'success' => true,
            'message' => 'Torneo eliminado.',
        ]);
    }
}
