<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Eixo;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InscricaoImportTodosMomentosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_confirmar_importacao_com_sessao_todos_os_momentos_inscreve_em_cada_atividade(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $eixo = Eixo::create(['nome' => 'Eixo import']);
        $evento = Evento::factory()->create([
            'user_id' => $admin->id,
            'eixo_id' => $eixo->id,
        ]);

        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'dia' => '2026-02-10',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'dia' => '2026-02-11',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);

        $pUser = User::factory()->create(['email' => 'import_todos@test.local']);
        $participante = Participante::create(['user_id' => $pUser->id]);

        $sessionKey = "import_preview_evento_{$evento->id}_todos";
        session([$sessionKey => [
            'modo_todos_momentos' => true,
            'atividade_id' => null,
            'rows' => [
                [
                    'nome' => 'Participante Planilha',
                    'email' => 'import_todos@test.local',
                    'cpf' => null,
                    'telefone' => null,
                    'municipio_id' => null,
                ],
            ],
        ]]);

        $response = $this->actingAs($admin)->post(route('inscricoes.confirmar', $evento), [
            'session_key' => $sessionKey,
        ]);

        $response->assertRedirect(route('eventos.show', $evento));

        $this->assertSame(2, Inscricao::query()
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->count());
    }
}
