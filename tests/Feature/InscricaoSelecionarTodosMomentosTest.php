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

class InscricaoSelecionarTodosMomentosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_inscreve_participante_em_todos_os_momentos_quando_atividade_id_vazio(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $eixo = Eixo::create(['nome' => 'Eixo teste']);
        $evento = Evento::factory()->create([
            'user_id' => $user->id,
            'eixo_id' => $eixo->id,
        ]);

        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'dia' => '2026-01-10',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'dia' => '2026-01-11',
            'hora_inicio' => '09:00:00',
            'hora_fim' => '10:00:00',
        ]);

        $pUser = User::factory()->create();
        $participante = Participante::create(['user_id' => $pUser->id]);

        $response = $this->actingAs($user)->post(route('inscricoes.selecionar.store', $evento), [
            'atividade_id' => '',
            'participantes' => [$participante->id],
        ]);

        $response->assertRedirect();
        $this->assertSame(2, Inscricao::query()
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->count());
    }

    public function test_evento_sem_momentos_retorna_erro_de_validacao(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $eixo = Eixo::create(['nome' => 'Eixo teste 2']);
        $evento = Evento::factory()->create([
            'user_id' => $user->id,
            'eixo_id' => $eixo->id,
        ]);

        $pUser = User::factory()->create();
        $participante = Participante::create(['user_id' => $pUser->id]);

        $response = $this->actingAs($user)->post(route('inscricoes.selecionar.store', $evento), [
            'atividade_id' => '',
            'participantes' => [$participante->id],
        ]);

        $response->assertSessionHasErrors('atividade_id');
    }
}
