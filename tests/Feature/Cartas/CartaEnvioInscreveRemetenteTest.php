<?php

namespace Tests\Feature\Cartas;

use App\Models\Inscricao;
use Illuminate\Http\UploadedFile;

class CartaEnvioInscreveRemetenteTest extends CartasBaseTest
{
    public function test_enviar_carta_inscreve_automaticamente_o_remetente_na_acao_de_cartas(): void
    {
        $this->assertDatabaseMissing('inscricaos', [
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);

        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseHas('inscricaos', [
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);
    }

    public function test_enviar_carta_nao_duplica_inscricao_de_remetente_ja_inscrito(): void
    {
        Inscricao::create([
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);

        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $this->assertDatabaseCount('inscricaos', 1);
    }
}
