<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\GameMatch;
use App\Models\MatchPlayer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clubs = Club::with('courts')->get();

        if ($clubs->isEmpty()) {
            $this->command->warn('No hay clubs disponibles. Ejecuta primero ClubSeeder.');
            return;
        }

        $users = User::all();
        if ($users->isEmpty()) {
            $this->command->warn('No hay usuarios disponibles.');
            return;
        }

        $matchTypes = ['singles', 'doubles', 'mixed_doubles'];
        $statuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        $now = Carbon::now();

        $this->command->info('Creando partidos de prueba...');

        foreach ($clubs as $club) {
            $courts = $club->courts;
            if ($courts->isEmpty()) {
                continue;
            }

            // Crear 8-15 partidos por club
            $matchesCount = rand(8, 15);

            for ($i = 0; $i < $matchesCount; $i++) {
                $court = $courts->random();
                $matchType = $matchTypes[array_rand($matchTypes)];
                $status = $statuses[array_rand($statuses)];
                $isCompetitive = rand(0, 10) > 2; // 80% competitivos

                // Fechas variadas: algunos en el pasado, algunos hoy, algunos en el futuro
                $dayOffset = match ($status) {
                    'completed', 'cancelled' => rand(-30, -1),
                    'in_progress' => 0,
                    'scheduled' => rand(0, 14),
                    default => 0,
                };

                $bookingDate = $now->copy()->addDays($dayOffset)->format('Y-m-d');
                $startHour = rand(7, 20);
                $startTime = sprintf('%02d:00', $startHour);
                $endTime = sprintf('%02d:00', $startHour + 1);

                // Crear o reutilizar una reserva
                $booking = Booking::create([
                    'user_id' => $users->random()->id,
                    'court_id' => $court->id,
                    'club_id' => $club->id,
                    'booking_date' => $bookingDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'duration_minutes' => 60,
                    'total_price' => $court->price_per_hour ?? rand(20000, 80000),
                    'status' => $status === 'cancelled' ? 'cancelled' : 'confirmed',
                    'players_count' => $matchType === 'doubles' ? 4 : 2,
                ]);

                $skillMin = rand(1, 5);
                $skillMax = rand($skillMin + 1, min($skillMin + 5, 10));

                $matchData = [
                    'booking_id' => $booking->id,
                    'club_id' => $club->id,
                    'court_id' => $court->id,
                    'match_type' => $matchType,
                    'status' => $status,
                    'is_competitive' => $isCompetitive,
                    'skill_level_min' => $skillMin,
                    'skill_level_max' => $skillMax,
                ];

                // Si está completado, agregar scores
                if ($status === 'completed') {
                    $team1Score = rand(0, 7);
                    $team2Score = rand(0, 7);
                    $matchData['team1_score'] = $team1Score;
                    $matchData['team2_score'] = $team2Score;
                    $matchData['winner_team'] = $team1Score > $team2Score ? 1 : 2;
                    $matchData['score_details'] = json_encode([
                        ['set' => 1, 'team1' => rand(0, 7), 'team2' => rand(0, 7)],
                        ['set' => 2, 'team1' => rand(0, 7), 'team2' => rand(0, 7)],
                        ['set' => 3, 'team1' => rand(0, 7), 'team2' => rand(0, 7)],
                    ]);
                } elseif ($status === 'in_progress') {
                    $matchData['team1_score'] = rand(0, 3);
                    $matchData['team2_score'] = rand(0, 3);
                }

                $match = GameMatch::create($matchData);

                // Agregar jugadores al partido
                $playersCount = $matchType === 'doubles' ? rand(2, 4) : rand(1, 2);
                $selectedUsers = $users->random($playersCount);

                foreach ($selectedUsers as $index => $user) {
                    $team = null;
                    if ($matchType === 'doubles') {
                        $team = $index < $playersCount / 2 ? 'team1' : 'team2';
                    }

                    $playerStatus = $status === 'completed' || $status === 'in_progress'
                        ? 'confirmed'
                        : (rand(0, 10) > 2 ? 'confirmed' : 'pending');

                    MatchPlayer::create([
                        'match_id' => $match->id,
                        'user_id' => $user->id,
                        'team' => $team,
                        'status' => $playerStatus,
                    ]);
                }
            }
        }

        $totalMatches = GameMatch::count();
        $this->command->info("Se crearon {$totalMatches} partidos de prueba exitosamente.");
    }
}
