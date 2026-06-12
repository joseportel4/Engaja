<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class DashboardPdfExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    /**
     * Substitui o builder real por um mock fluente, evitando disparar o
     * Browsershot/Chromium, e captura os dados passados à view do PDF.
     *
     * @return array<string, mixed>
     */
    private function capturarDadosDoPdf(callable $acao): array
    {
        $captured = [];

        $builder = Mockery::mock(PdfBuilder::class);
        $builder->shouldReceive('format')->andReturnSelf();
        $builder->shouldReceive('withAlfaEjaBrand')->andReturnSelf();
        $builder->shouldReceive('download')->andReturnSelf();
        $builder->shouldReceive('toResponse')->andReturn(response('ok'));

        Pdf::shouldReceive('view')
            ->once()
            ->andReturnUsing(function ($view, $data) use (&$captured, $builder) {
                $captured = $data;

                return $builder;
            });

        $acao();

        return $captured;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    public function test_pdf_e_truncado_quando_excede_o_teto_de_atividades(): void
    {
        config(['dashboard.pdf.max_atividades' => 2]);

        $evento = Evento::factory()->create();
        Atividade::factory()->count(3)->create(['evento_id' => $evento->id]);

        $admin = $this->admin();

        $dados = $this->capturarDadosDoPdf(function () use ($admin) {
            $this->actingAs($admin)->get(route('dashboard.export'))->assertOk();
        });

        $this->assertTrue($dados['truncado']);
        $this->assertSame(3, $dados['totalAtividades']);
        $this->assertSame(2, $dados['maxAtividades']);
        $this->assertCount(2, $dados['atividades']);
    }

    public function test_pdf_nao_e_truncado_quando_dentro_do_teto(): void
    {
        config(['dashboard.pdf.max_atividades' => 50]);

        $evento = Evento::factory()->create();
        Atividade::factory()->count(3)->create(['evento_id' => $evento->id]);

        $admin = $this->admin();

        $dados = $this->capturarDadosDoPdf(function () use ($admin) {
            $this->actingAs($admin)->get(route('dashboard.export'))->assertOk();
        });

        $this->assertFalse($dados['truncado']);
        $this->assertSame(3, $dados['totalAtividades']);
        $this->assertCount(3, $dados['atividades']);
    }
}
