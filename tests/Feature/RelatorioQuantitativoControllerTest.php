<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatorioQuantitativoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function criarAtividade(): Atividade
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $municipio = Municipio::factory()->create();

        return Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => $municipio->id,
            'publico_esperado' => 50,
        ]);
    }

    public function test_aba_momento_renderiza_grid_com_subtotal(): void
    {
        $this->criarAtividade();

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'momento']))
            ->assertOk()
            ->assertSee('grid-relatorio-momento', false)
            ->assertSee('Formação Alfa-EJA')
            ->assertSee('Subtotal');
    }

    public function test_aba_total_geral_renderiza_grid(): void
    {
        $this->criarAtividade();

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral']))
            ->assertOk()
            ->assertSee('grid-relatorio-total-geral', false);
    }

    public function test_total_geral_ignora_filtro_de_municipio(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $municipioA = Municipio::factory()->create(['nome' => 'Alpha City']);
        $municipioB = Municipio::factory()->create(['nome' => 'Beta City']);

        Atividade::factory()->create(['evento_id' => $evento->id, 'municipio_id' => $municipioA->id, 'publico_esperado' => 10]);
        Atividade::factory()->create(['evento_id' => $evento->id, 'municipio_id' => $municipioB->id, 'publico_esperado' => 20]);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        // Mesmo passando municipio_id, o relatório continua listando todos os municípios.
        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral', 'municipio_id' => $municipioA->id]))
            ->assertOk()
            ->assertSee('Alpha City')
            ->assertSee('Beta City');
    }
}
