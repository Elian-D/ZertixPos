<?php

namespace Database\Factories\Sales\Pos;

use App\Models\Sales\Pos\PosSession;
use App\Models\Sales\Pos\PosTerminal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PosSessionFactory extends Factory
{
    protected $model = PosSession::class;

    /**
     * Por defecto genera una sesión ya cerrada y cuadrada (sin descuadre) — para
     * historial demo backdateado (que PosSessionService::open()/close() no soporta,
     * fuerzan `now()`), el comando zertix:seed-demo crea la sesión directo con
     * timestamps propios en vez de usar este factory en su forma por defecto.
     */
    public function definition(): array
    {
        $openedAt = fake()->dateTimeBetween('-30 days', '-8 hours');
        $closedAt = (clone $openedAt)->modify('+'.random_int(4, 9).' hours');
        $opening = fake()->randomFloat(2, 500, 2000);

        return [
            'terminal_id' => PosTerminal::query()->inRandomOrder()->value('id'),
            'user_id' => User::query()->inRandomOrder()->value('id'),
            'opened_by_user_id' => fn (array $attrs) => $attrs['user_id'],
            'closed_by_user_id' => fn (array $attrs) => $attrs['user_id'],
            'status' => PosSession::STATUS_CLOSED,
            'opened_at' => $openedAt,
            'closed_at' => $closedAt,
            'opening_balance' => $opening,
            'closing_balance' => $opening,
            'expected_balance' => $opening,
            'difference' => 0,
        ];
    }

    public function open(): static
    {
        return $this->state(fn () => [
            'status' => PosSession::STATUS_OPEN,
            'closed_at' => null,
            'closed_by_user_id' => null,
            'closing_balance' => null,
            'expected_balance' => null,
            'difference' => null,
        ]);
    }
}
