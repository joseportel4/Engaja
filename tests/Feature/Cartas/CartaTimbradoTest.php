<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\Participante;
use App\Models\User;
use App\Services\Cartas\CartaTimbradoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CartaTimbradoTest extends TestCase
{
    use RefreshDatabase;

    public function test_aplicar_gera_pdf_final_e_salva_metadados(): void
    {
        Storage::fake('local');

        $mensagem = CartaMensagem::factory()->create([
            'texto' => "Olá, querido educando!\n\nRecebi sua carta com esperança. Um abraço — çãõ.",
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
        ]);

        (new CartaTimbradoService)->aplicar($mensagem);

        $mensagem->refresh();

        $this->assertNotNull($mensagem->arquivo_final_path);
        $this->assertSame('application/pdf', $mensagem->arquivo_final_mime);
        $this->assertGreaterThan(0, $mensagem->arquivo_final_tamanho);
        $this->assertNotNull($mensagem->timbrado_aplicado_em);
        Storage::disk('local')->assertExists($mensagem->arquivo_final_path);
    }

    public function test_resposta_digitada_do_voluntario_aplica_timbrado(): void
    {
        Storage::fake('local');

        $voluntario = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
        ]);

        $educando = Participante::factory()->create([
            'user_id' => User::factory()->create(['sistema_origem' => User::SISTEMA_ENGAJA]),
        ]);

        $carta = Carta::factory()->create([
            'educando_participante_id' => $educando->id,
            'voluntario_user_id' => $voluntario->id,
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'status' => CartaMensagem::STATUS_APROVADA,
        ]);

        $response = $this->actingAs($voluntario)->post(route('cartas.cartas.respond', $carta), [
            'modo_resposta' => 'digitada',
            'texto' => 'Minha resposta digitada com carinho.',
        ]);

        $response->assertRedirect();

        $resposta = $carta->mensagens()->where('rodada', 2)->first();

        $this->assertNotNull($resposta);
        $this->assertNotNull($resposta->arquivo_final_path);
        Storage::disk('local')->assertExists($resposta->arquivo_final_path);
    }
}
