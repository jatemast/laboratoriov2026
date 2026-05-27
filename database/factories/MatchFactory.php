<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\GameMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        $matchTypes = ['singles', 'doubles', 'mixed_doubles'];
        $statuses = ['scheduled', 'in_progress', 'completed', 'cancelled'];
        $isCompetitive = $this->faker->boolean(70);

        return [
            'booking_id' => Booking::factory(),
            'club_id' => Club::factory(),
            'court_id' => Court::factory(),
            'match_type' => $this->faker->randomElement($matchTypes),
            'status' => $this->faker->randomElement($statuses),
            'team1_players' => null,
            'team2_players' => null,
            'team1_score' => null,
            'team2_score' => null,
            'score_details' => null,
            'winner_team' => null,
            'is_competitive' => $isCompetitive,
            'skill_level_min' => $this->faker->numberBetween(1, 5),
            'skill_level_max' => $this->faker->numberBetween(5, 10),
        ];
    }

    /**
     * Partido programado (disponible para unirse).
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
            'is_competitive' => true,
        ]);
    }

    /**
     * Partido completado.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'team1_score' => $this->faker->numberBetween(0, 7),
            'team2_score' => $this->faker->numberBetween(0, 7),
            'winner_team' => $this->faker->randomElement([1, 2]),
            'score_details' => [
                ['set' => 1, 'team1' => $this->faker->numberBetween(0, 7), 'team2' => $this->faker->numberBetween(0, 7)],
                ['set' => 2, 'team1' => $this->faker->numberBetween(0, 7), 'team2' => $this->faker->numberBetween(0, 7)],
            ],
        ]);
    }

    /**
     * Partido en progreso.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'team1_score' => $this->faker->numberBetween(0, 3),
            'team2_score' => $this->faker->numberBetween(0, 3),
        ]);
    }
}
