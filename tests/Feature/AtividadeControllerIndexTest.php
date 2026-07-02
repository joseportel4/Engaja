<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtividadeControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados_para_usuario_autenticado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $evento = Evento::factory()->create();
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Encontro de abertura',
        ]);

        $this->actingAs($user)
            ->get(route('eventos.atividades.index', $evento))
            ->assertOk()
            ->assertSee('grid-atividades', false)
            ->assertSee('Encontro de abertura')
            ->assertSee('Gerenciar');
    }

    public function test_index_oculta_acoes_para_visitante_nao_autenticado(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Encontro público',
        ]);

        $this->get(route('eventos.atividades.index', $evento))
            ->assertOk()
            ->assertSee('Encontro público')
            ->assertDontSee('Gerenciar');
    }
}
