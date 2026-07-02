<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_usuarios_selecionaveis(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $comParticipante = User::factory()->create(['name' => 'Usuário Com Participante']);

        $semParticipante = User::factory()->create(['name' => 'Usuário Sem Participante']);
        $semParticipante->participante()->delete();

        $this->actingAs($admin)
            ->get(route('usuarios.index'))
            ->assertOk()
            ->assertSee('grid-usuarios', false)
            ->assertSee('data-row-selection="multiple"', false)
            ->assertSee('data-row-selectable-field="_selectable"', false)
            ->assertSee('Usuário Com Participante')
            ->assertSee('&quot;_selectable&quot;:true', false)
            ->assertSee('&quot;_selectable&quot;:false', false);
    }
}
