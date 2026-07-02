<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuariosSemVinculoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        User::factory()->create();

        $this->actingAs($user)
            ->get(route('usuarios.sem-vinculo.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false);
    }
}
