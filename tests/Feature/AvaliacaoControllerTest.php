<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvaliacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $atividade = Atividade::factory()->create([
            'evento_id' => Evento::factory(),
            'descricao' => 'Encontro de abertura',
        ]);
        Avaliacao::factory()->create(['atividade_id' => $atividade->id]);

        $this->actingAs($user)
            ->get(route('avaliacoes.index'))
            ->assertOk()
            ->assertSee('grid-avaliacoes', false)
            ->assertSee('Encontro de abertura');
    }
}
