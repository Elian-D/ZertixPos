<?php

namespace Tests\Feature\Pos;

use App\Models\Geo\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TestCase;

class PosQuickCustomerTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpPosWorkspace;

    public function test_a_quick_customer_can_be_created_from_the_pos_modal(): void
    {
        $this->setUpPosWorkspace();

        $response = $this->actingAs($this->cashier)
            ->postJson(route('sales.pos.quick-customer.store'), [
                'name' => 'Cliente Express',
                'phone' => '809-555-0000',
                'state_id' => State::query()->value('id'),
            ]);

        $response->assertCreated();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('clients', [
            'name' => 'Cliente Express',
            'phone' => '809-555-0000',
        ]);
    }
}
