<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use SimpleSoftwareIO\QrCode\Generator;
use Tests\TestCase;

class AvaliacaoUniversaisControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        // Evita depender da extensão imagick (usada pelo backend PNG do
        // simple-qrcode) só para este teste de renderização da tabela.
        $qrCodeMock = Mockery::mock(Generator::class);
        $qrCodeMock->shouldReceive('format', 'style', 'color', 'eye', 'eyeColor', 'size', 'margin', 'merge', 'errorCorrection')
            ->andReturnSelf();
        $qrCodeMock->shouldReceive('generate')->andReturn('');
        $this->app->instance(Generator::class, $qrCodeMock);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $template = TemplateAvaliacao::factory()->create(['nome' => 'Modelo Universal']);
        Avaliacao::factory()->create([
            'atividade_id' => null,
            'template_avaliacao_id' => $template->id,
            'descricao_universal' => 'Avaliação de satisfação geral',
        ]);

        $this->actingAs($user)
            ->get(route('avaliacoes-universais.index'))
            ->assertOk()
            ->assertSee('grid-avaliacoes-universais', false)
            ->assertSee('Avaliação de satisfação geral')
            ->assertSee('Modelo Universal');
    }
}
