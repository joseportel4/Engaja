<?php

namespace Tests\Feature\Cartas;

use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartaRemetenteFilterTest extends TestCase
{
    use RefreshDatabase;

    private function participanteInscrito(bool $inscrito): Participante
    {
        $user = User::factory()->create(['sistema_origem' => User::SISTEMA_ENGAJA]);
        $participante = Participante::factory()->create(['user_id' => $user->id]);

        $evento = Evento::factory()->create(['is_cartas' => $inscrito]);
        Inscricao::create([
            'evento_id' => $evento->id,
            'participante_id' => $participante->id,
        ]);

        return $participante;
    }

    public function test_lista_de_remetentes_traz_apenas_usuarios_engaja_com_participante(): void
    {
        $inscrito = $this->participanteInscrito(true);
        $foraDaAcao = $this->participanteInscrito(false);
        $usuarioCartas = User::factory()->create(['sistema_origem' => User::SISTEMA_CARTAS]);

        Role::findOrCreate('cartas_gestao', 'web');
        $gestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
        ]);
        $gestor->assignRole('cartas_gestao');

        $response = $this->actingAs($gestor)->get(route('cartas.dashboard'));

        $response->assertOk();

        $ids = collect($response->viewData('engajaUsers'))->pluck('id');

        // Usuários Engaja com participante DEVEM aparecer
        $this->assertTrue($ids->contains($inscrito->user_id));
        $this->assertTrue($ids->contains($foraDaAcao->user_id));

        // Usuário do sistema Cartas NÃO deve aparecer
        $this->assertFalse($ids->contains($usuarioCartas->id));
    }
}
