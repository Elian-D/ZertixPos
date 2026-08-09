<?php

namespace Database\Factories\Sales\Pos;

use App\Models\Sales\Pos\PosCashMovement;
use App\Models\Sales\Pos\PosSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosCashMovementFactory extends Factory
{
    protected $model = PosCashMovement::class;

    public function definition(): array
    {
        return [
            'pos_session_id' => PosSession::factory(),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'accounting_account_id' => null,
            'type' => fake()->randomElement([PosCashMovement::TYPE_IN, PosCashMovement::TYPE_OUT]),
            'amount' => fake()->randomFloat(2, 100, 1000),
            'reason' => fake()->sentence(),
            'reference' => null,
            'metadata' => null,
        ];
    }

    public function opening(): static
    {
        return $this->state(fn () => [
            'type' => PosCashMovement::TYPE_IN,
            'reason' => 'Fondo inicial de caja',
        ]);
    }

    public function out(): static
    {
        return $this->state(fn () => [
            'type' => PosCashMovement::TYPE_OUT,
        ]);
    }
}
