<?php

namespace Database\Factories\Clients;

use App\Models\Clients\BusinessType;
use App\Models\Clients\Client;
use App\Models\Clients\PointOfSale;
use App\Models\Geo\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointOfSaleFactory extends Factory
{
    protected $model = PointOfSale::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::query()->inRandomOrder()->value('id') ?? Client::factory(),
            'business_type_id' => BusinessType::query()->inRandomOrder()->value('id') ?? 1,
            'name' => fake()->company().' - '.fake()->city(),
            'provincia_id' => Province::inRandomOrder()->value('id') ?? 1,
            'city' => fake()->city(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'contact_name' => fake()->name(),
            'contact_phone' => fake()->phoneNumber(),
            'active' => fake()->boolean(90),
        ];
    }
}
