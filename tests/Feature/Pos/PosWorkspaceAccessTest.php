<?php

namespace Tests\Feature\Pos;

use Tests\Feature\Pos\Concerns\SetsUpPosWorkspace;
use Tests\TenantTestCase;

class PosWorkspaceAccessTest extends TenantTestCase
{
    use SetsUpPosWorkspace;

    public function test_redirects_to_lobby_when_there_is_no_open_session(): void
    {
        $this->setUpPosWorkspace();

        $response = $this->actingAs($this->cashier)
            ->get(route('sales.pos.workspace', $this->terminal));

        $response->assertRedirect(route('sales.pos.index'));
        $response->assertSessionHas('error');
    }

    public function test_loads_the_workspace_when_the_cashier_has_an_open_session(): void
    {
        $this->setUpPosWorkspace();
        $this->openPosSession();

        $response = $this->actingAs($this->cashier)
            ->get(route('sales.pos.workspace', $this->terminal));

        $response->assertOk();
        $response->assertSee($this->terminal->name);
        $response->assertSee($this->product->name);
    }
}
