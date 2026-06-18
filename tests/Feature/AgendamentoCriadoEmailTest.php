<?php

namespace Tests\Feature;

use App\Mail\AgendamentoCriadoMail;
use App\Models\AtividadeAcao;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgendamentoCriadoEmailTest extends TestCase
{
    use RefreshDatabase;

    private AtividadeAcao $atividadeAcao;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $regiao = Regiao::create(['nome' => 'Nordeste']);
        $estado = Estado::create(['regiao_id' => $regiao->id, 'nome' => 'Bahia', 'sigla' => 'BA']);
        $this->municipio = Municipio::create(['estado_id' => $estado->id, 'nome' => 'Salvador']);
        $this->atividadeAcao = AtividadeAcao::create(['nome' => 'Formação de Professores', 'usa_turmas' => false]);
    }

    public function test_criar_agendamento_envia_email_para_administradores(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'admin@test.local']);
        $admin->assignRole('administrador');

        $criador = User::factory()->create(['email' => 'articulador@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('admin@test.local'));
    }

    public function test_criar_agendamento_envia_email_para_gerentes_e_eq_pedagogica(): void
    {
        Mail::fake();

        $gerente = User::factory()->create(['email' => 'gerente@test.local']);
        $gerente->assignRole('gerente');

        $eq = User::factory()->create(['email' => 'eq@test.local']);
        $eq->assignRole('eq_pedagogica');

        $criador = User::factory()->create(['email' => 'articulador2@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('gerente@test.local'));
        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('eq@test.local'));
    }

    public function test_criar_agendamento_nao_envia_email_para_roles_sem_permissao_efetivar(): void
    {
        Mail::fake();

        $participante = User::factory()->create(['email' => 'participante@test.local']);
        $participante->assignRole('participante');

        $sme = User::factory()->create(['email' => 'sme@test.local']);
        $sme->assignRole('SME');

        $criador = User::factory()->create(['email' => 'articulador3@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertNotQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('participante@test.local'));
        Mail::assertNotQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('sme@test.local'));
    }
}
