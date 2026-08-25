<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CartaDownloadBatchTest extends CartasBaseTest
{
    protected function setUp(): void
    {
        parent::setUp();
        Evento::where('id', '!=', $this->eventoCartas->id)->update(['is_cartas' => false]);
    }

    public function test_gestor_dashboard_without_filters_has_unchecked_checkboxes(): void
    {
        $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.dashboard'));

        $response->assertOk();
        $response->assertSee('1 de 1 selecionadas');
        $response->assertSee('checked style="width: 18px;', false);
    }

    public function test_gestor_dashboard_with_municipio_filter_checks_boxes_by_default(): void
    {
        $regiao = Regiao::firstOrCreate(['nome' => 'Norte']);
        $estado = Estado::factory()->create(['regiao_id' => $regiao->id]);
        $municipioTarget = Municipio::factory()->create(['nome' => 'Santarém', 'estado_id' => $estado->id]);

        $this->educando->update(['municipio_id' => $municipioTarget->id]);
        $this->educando->refresh();

        $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.dashboard', ['municipio_id' => $municipioTarget->id]));

        $response->assertOk();
        $response->assertSee('1 de 1 selecionadas');
        $response->assertSee('checked', false);
    }

    public function test_gestor_dashboard_with_status_filter_filters_correctly(): void
    {
        $cartaEnviada = $this->criarCartaParaVoluntario();
        $cartaEnviada->update(['codigo' => 'CARTA_ENV_111']);

        $cartaRespondida = $this->criarCartaParaVoluntario();
        $cartaRespondida->update(['status' => Carta::STATUS_RESPONDIDA, 'codigo' => 'CARTA_RESP_999']);

        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.dashboard', ['status' => 'respondida']));

        $response->assertOk();
        $response->assertSee('CARTA_RESP_999');
        $response->assertDontSee('CARTA_ENV_111');
        $response->assertSee('1 de 1 selecionadas');
    }

    public function test_gestor_dashboard_combined_filters(): void
    {
        $regiao = Regiao::firstOrCreate(['nome' => 'Nordeste I']);
        $estado = Estado::factory()->create(['regiao_id' => $regiao->id]);
        $municipioTarget = Municipio::factory()->create(['nome' => 'Fortaleza', 'estado_id' => $estado->id]);

        $this->educando->update(['municipio_id' => $municipioTarget->id]);
        $this->educando->refresh();

        $cartaTarget = $this->criarCartaParaVoluntario();
        $cartaTarget->update(['status' => Carta::STATUS_RESPONDIDA, 'codigo' => 'CARTA_MATCH_777']);

        $cartaOutra = $this->criarCartaParaVoluntario();
        $cartaOutra->update(['codigo' => 'CARTA_OTHER_333']);

        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.dashboard', [
                'municipio_id' => $municipioTarget->id,
                'status' => 'respondida',
                'q' => 'CARTA_MATCH_777',
            ]));

        $response->assertOk();
        $response->assertSee('CARTA_MATCH_777');
        $response->assertDontSee('CARTA_OTHER_333');
        $response->assertSee('1 de 1 selecionadas');
    }

    public function test_download_batch_downloads_only_selected_carta_ids(): void
    {
        Storage::fake('local');

        $userRemetente1 = User::factory()->create(['name' => 'Ana Remetente', 'sistema_origem' => User::SISTEMA_ENGAJA]);
        $participante1 = $userRemetente1->participante;

        $userRemetente2 = User::factory()->create(['name' => 'Bruno Remetente', 'sistema_origem' => User::SISTEMA_ENGAJA]);
        $participante2 = $userRemetente2->participante;

        // Carta 1 com arquivo
        $carta1 = Carta::create([
            'educando_participante_id' => $participante1->id,
            'voluntario_user_id' => $this->voluntario->id,
            'evento_id' => $this->eventoCartas->id,
            'codigo' => 'CARTA_SEL_1',
            'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
            'rodada_atual' => 1,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);
        Storage::disk('local')->put('cartas/doc1.pdf', 'Conteudo PDF 1');
        CartaMensagem::create([
            'carta_id' => $carta1->id,
            'rodada' => 1,
            'remetente_participante_id' => $participante1->id,
            'destinatario_user_id' => $this->voluntario->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_MANUSCRITO,
            'status' => CartaMensagem::STATUS_APROVADA,
            'anexo_original_path' => 'cartas/doc1.pdf',
            'anexo_original_nome' => 'doc1.pdf',
            'anexo_original_mime' => 'application/pdf',
            'enviada_em' => now(),
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        // Carta 2 com arquivo
        $carta2 = Carta::create([
            'educando_participante_id' => $participante2->id,
            'voluntario_user_id' => $this->voluntario->id,
            'evento_id' => $this->eventoCartas->id,
            'codigo' => 'CARTA_SEL_2',
            'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
            'rodada_atual' => 1,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);
        Storage::disk('local')->put('cartas/doc2.pdf', 'Conteudo PDF 2');
        CartaMensagem::create([
            'carta_id' => $carta2->id,
            'rodada' => 1,
            'remetente_participante_id' => $participante2->id,
            'destinatario_user_id' => $this->voluntario->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_MANUSCRITO,
            'status' => CartaMensagem::STATUS_APROVADA,
            'anexo_original_path' => 'cartas/doc2.pdf',
            'anexo_original_nome' => 'doc2.pdf',
            'anexo_original_mime' => 'application/pdf',
            'enviada_em' => now(),
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        // Baixa apenas a carta 1 por ID selecionado
        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.download-batch', [
                'carta_ids' => [$carta1->id],
            ]));

        $response->assertOk();
        $this->assertEquals('application/zip', $response->headers->get('content-type'));

        // Salvar temp para verificar conteudo do ZIP
        $zipPath = tempnam(sys_get_temp_dir(), 'test_zip');
        file_put_contents($zipPath, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath));
        $this->assertSame(1, $zip->numFiles);

        $filenameInZip = $zip->getNameIndex(0);
        $this->assertStringContainsString('Ana_Remetente', $filenameInZip);
        $this->assertStringNotContainsString('Bruno_Remetente', $filenameInZip);

        $zip->close();
        @unlink($zipPath);
    }
}
