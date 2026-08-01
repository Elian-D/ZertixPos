<?php

namespace App\DTOs\Clients;

use App\Models\Configuration\EstadosCliente;

class QuickClientDTO
{
    public function __construct(
        public string $name,
        public ?string $tax_id = null,
        public ?int $tax_identifier_type_id = null,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $address = null,
        public ?int $state_id = null,
        public ?string $city = null,
        public string $type = 'individual',
        public ?int $estado_cliente_id = null,
        public float $credit_limit = 0,
        public int $payment_terms = 0,
    ) {}

    public static function fromRequest(array $data): self
    {
        $config = general_config();

        return new self(
            name: $data['name'],
            tax_id: $data['tax_id'] ?? null,
            tax_identifier_type_id: $data['tax_identifier_type_id'] ?? null,
            phone: $data['phone'] ?? null,
            email: $data['email'] ?? null,
            address: $data['address'] ?? null,
            state_id: $data['state_id'] ?? $config?->state_id,
            city: $data['city'] ?? $config?->city ?? $config?->ciudad ?? 'N/A',
            estado_cliente_id: EstadosCliente::where('nombre', 'Activo')->value('id')
                ?? EstadosCliente::query()->value('id'),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
