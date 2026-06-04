<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentPlayer;
use App\Models\TournamentMatch;
use Illuminate\Support\Facades\DB;

class TournamentService
{
    /**
     * Listar torneos con filtros.
     */
    public function list(array $filters = [])
    {
        $query = Tournament::with(['club', 'creator.profile']);

        if (!empty($filters['club_id'])) {
            $query->byClub($filters['club_id']);
        }

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['sport_type'])) {
            $query->where('sport_type', $filters['sport_type']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Crear un torneo.
     */
    public function create(array $data, int $creatorId): Tournament
    {
        return DB::transaction(function () use ($data, $creatorId) {
            $tournament = Tournament::create([
                'club_id' => $data['club_id'],
                'creator_id' => $creatorId,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'sport_type' => $data['sport_type'] ?? 'padel',
                'tournament_type' => $data['tournament_type'] ?? 'elimination',
                'match_type' => $data['match_type'] ?? 'doubles',
                'max_teams' => $data['max_teams'] ?? 8,
                'min_players_per_team' => $data['min_players_per_team'] ?? 2,
                'max_players_per_team' => $data['max_players_per_team'] ?? 2,
                'skill_level_min' => $data['skill_level_min'] ?? null,
                'skill_level_max' => $data['skill_level_max'] ?? null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'] ?? null,
                'entry_fee' => $data['entry_fee'] ?? 0,
                'prize' => $data['prize'] ?? null,
                'status' => 'registration',
            ]);

            // El creador se une automáticamente
            $tournament->players()->create([
                'user_id' => $creatorId,
                'team_name' => $data['team_name'] ?? null,
                'status' => 'approved',
            ]);

            return $tournament->load(['club', 'creator.profile']);
        });
    }

    /**
     * Mostrar un torneo.
     */
    public function findById(int $id): ?Tournament
    {
        return Tournament::with([
            'club',
            'creator.profile',
            'players.user.profile',
            'tournamentMatches.team1Player1.profile',
            'tournamentMatches.team1Player2.profile',
            'tournamentMatches.team2Player1.profile',
            'tournamentMatches.team2Player2.profile',
            'tournamentMatches.winner.profile',
        ])->findOrFail($id);
    }

    /**
     * Inscribirse a un torneo.
     */
    public function register(int $tournamentId, int $userId, ?string $teamName = null): TournamentPlayer
    {
        $tournament = Tournament::findOrFail($tournamentId);

        if ($tournament->status !== 'registration') {
            throw new \Exception('El torneo no está en período de inscripción.');
        }

        $playerCount = $tournament->players()->where('status', 'approved')->count();
        if ($playerCount >= $tournament->max_teams) {
            throw new \Exception('El torneo ha alcanzado el máximo de participantes.');
        }

        return $tournament->players()->create([
            'user_id' => $userId,
            'team_name' => $teamName,
            'status' => 'approved', // Auto-aprobar para facilitar pruebas y uso directo
        ]);
    }

    /**
     * Aprobar/rechazar inscripción.
     */
    public function updatePlayerStatus(int $tournamentId, int $playerId, string $status): TournamentPlayer
    {
        $player = TournamentPlayer::where('tournament_id', $tournamentId)
            ->findOrFail($playerId);
        $player->update(['status' => $status]);
        return $player->fresh();
    }

    /**
     * Iniciar torneo (generar bracket).
     */
    public function startTournament(int $tournamentId): Tournament
    {
        $tournament = Tournament::with('players')->findOrFail($tournamentId);

        if ($tournament->status !== 'registration') {
            throw new \Exception('El torneo no está en período de inscripción.');
        }

        // Auto-aprobar todos los jugadores pendientes para agilizar el proceso
        $tournament->players()->where('status', 'pending')->update(['status' => 'approved']);

        $approvedPlayers = $tournament->players()->where('status', 'approved')->get();
        if ($approvedPlayers->count() < 2) {
            throw new \Exception('Se necesitan al menos 2 equipos para iniciar.');
        }

        // Generar bracket de eliminación simple
        $players = $approvedPlayers->shuffle();
        $numPlayers = $players->count();
        $rounds = ceil(log($numPlayers, 2));
        $totalSlots = pow(2, $rounds);

        // Crear partidos de primera ronda
        $position = 1;
        for ($i = 0; $i < $numPlayers; $i += 2) {
            $p1 = $players[$i] ?? null;
            $p2 = $players[$i + 1] ?? null;

            TournamentMatch::create([
                'tournament_id' => $tournamentId,
                'round' => 1,
                'position' => $position,
                'team1_player1_id' => $p1?->user_id,
                'team2_player1_id' => $p2?->user_id,
                'status' => 'pending',
            ]);
            $position++;
        }

        // Si hay byes (número impar de equipos)
        if ($numPlayers < $totalSlots) {
            $byes = $totalSlots - $numPlayers;
            for ($i = 0; $i < $byes; $i++) {
                TournamentMatch::create([
                    'tournament_id' => $tournamentId,
                    'round' => 1,
                    'position' => $position,
                    'status' => 'bye',
                ]);
                $position++;
            }
        }

        $tournament->update(['status' => 'in_progress']);

        return $tournament->fresh()->load(['tournamentMatches', 'players']);
    }

    /**
     * Actualizar resultado de un partido de torneo.
     */
    public function updateMatchResult(int $matchId, int $team1Score, int $team2Score, int $winnerId): TournamentMatch
    {
        return DB::transaction(function () use ($matchId, $team1Score, $team2Score, $winnerId) {
            $tournamentMatch = TournamentMatch::findOrFail($matchId);
            $tournamentMatch->update([
                'team1_score' => $team1Score,
                'team2_score' => $team2Score,
                'winner_id' => $winnerId,
                'status' => 'completed',
            ]);

            // Avanzar ganador a siguiente ronda
            $nextRound = $tournamentMatch->round + 1;
            $nextPosition = ceil($tournamentMatch->position / 2);

            $nextMatch = TournamentMatch::where('tournament_id', $tournamentMatch->tournament_id)
                ->where('round', $nextRound)
                ->where('position', $nextPosition)
                ->first();

            if ($nextMatch) {
                if ($tournamentMatch->position % 2 === 1) {
                    $nextMatch->update(['team1_player1_id' => $winnerId]);
                } else {
                    $nextMatch->update(['team2_player1_id' => $winnerId]);
                }
            }

            // Verificar si el torneo terminó
            $remainingMatches = TournamentMatch::where('tournament_id', $tournamentMatch->tournament_id)
                ->where('status', 'pending')
                ->where('round', $nextRound)
                ->count();

            if ($remainingMatches === 0 && !$nextMatch) {
                Tournament::where('id', $tournamentMatch->tournament_id)
                    ->update(['status' => 'completed']);
            }

            return $tournamentMatch->fresh();
        });
    }

    /**
     * Eliminar un torneo.
     */
    public function delete(int $id): bool
    {
        $tournament = Tournament::findOrFail($id);
        return $tournament->delete();
    }
}
