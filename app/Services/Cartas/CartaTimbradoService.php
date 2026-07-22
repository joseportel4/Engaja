<?php

namespace App\Services\Cartas;

use App\Models\Cartas\CartaMensagem;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class CartaTimbradoService
{
    /**
     * Gera o PDF final aplicando o texto digitado sobre o papel timbrado
     * e persiste os metadados do arquivo final na mensagem.
     */
    public function aplicar(CartaMensagem $mensagem): void
    {
        $texto = trim((string) $mensagem->texto);

        if ($texto === '') {
            throw new RuntimeException('Não há texto para aplicar ao timbrado.');
        }

        $conteudo = $this->render($texto);

        $path = "cartas/{$mensagem->carta_id}/finais/{$mensagem->id}.pdf";
        Storage::disk('local')->put($path, $conteudo);

        $mensagem->forceFill([
            'arquivo_final_path' => $path,
            'arquivo_final_nome' => "carta-{$mensagem->carta_id}-rodada-{$mensagem->rodada}.pdf",
            'arquivo_final_mime' => 'application/pdf',
            'arquivo_final_tamanho' => strlen($conteudo),
            'timbrado_aplicado_em' => now(),
        ])->save();
    }

    /**
     * Renderiza o texto sobre o papel timbrado e devolve o conteúdo binário
     * do PDF, sem persistir nada. Usado por `aplicar()` e pelo comando
     * `cartas:preview-timbrado` para testar o layout sem passar pelo fluxo
     * completo de carta.
     */
    public function render(string $texto): string
    {
        $config = config('cartas.timbrado');
        $modelo = $config['path'];

        if (! is_file($modelo)) {
            throw new RuntimeException("Modelo de papel timbrado não encontrado em: {$modelo}");
        }

        // Reaplica o timbrado em cada página (inclusive nas quebras automáticas)
        // via Header(), que o FPDF chama a cada AddPage().
        $pdf = new class('P', 'mm', 'A5') extends Fpdi
        {
            public $templateId = null;

            public function Header(): void
            {
                if ($this->templateId !== null) {
                    $this->useTemplate($this->templateId, 0, 0, null, null, true);
                }
            }
        };

        $pdf->setSourceFile($modelo);
        $pdf->templateId = $pdf->importPage(1);

        $pdf->AddFont($config['font_family'], '', $config['font_file'], $config['font_dir']);

        // Desligado de propósito: cada linha é escrita em várias Cell() (uma
        // por palavra, para permitir justificar). Se o auto page break do
        // FPDF ficasse ligado, ele podia disparar no meio dessas células —
        // cortando a linha ao meio e "vazando" o resto dela para o topo da
        // página seguinte. Por isso a quebra é feita manualmente, sempre
        // entre linhas (ver `precisaQuebrarPagina()`).
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins($config['margin_left'], $config['start_top'], $config['margin_right']);
        $pdf->AddPage();
        $pdf->SetXY($config['margin_left'], $config['start_top']);
        $pdf->SetFont($config['font_family'], '', $config['font_size']);
        $pdf->SetTextColor(45, 45, 45);

        foreach ($this->paragrafos($texto) as $paragrafo) {
            $this->escreverParagrafo($pdf, $config, $paragrafo);
        }

        return $pdf->Output('S');
    }

    /**
     * Escreve um parágrafo quebrando linhas manualmente (em vez de MultiCell)
     * para poder reduzir a largura disponível quando a linha cair sobre a
     * ilustração do modelo (ver `cartas.timbrado.obstaculo`).
     *
     * @param  array<string, mixed>  $config
     */
    private function escreverParagrafo(Fpdi $pdf, array $config, string $paragrafo): void
    {
        if ($paragrafo === '') {
            $this->quebrarPaginaSeNecessario($pdf, $config);
            $pdf->Ln($config['line_height']);

            return;
        }

        $palavrasLinha = [];
        $this->quebrarPaginaSeNecessario($pdf, $config);

        foreach (preg_split('/\s+/', $paragrafo) as $palavra) {
            $tentativa = [...$palavrasLinha, $palavra];
            $largura = $this->larguraDisponivel($pdf, $config);

            if ($palavrasLinha !== [] && $this->larguraTexto($pdf, implode(' ', $tentativa)) > $largura) {
                $this->escreverLinha($pdf, $config, $palavrasLinha, justificar: true);
                $palavrasLinha = [$palavra];
                // Checa (e quebra, se preciso) ANTES de medir a largura da
                // próxima linha — senão o encaixe das palavras é decidido
                // com a largura de antes da quebra (da página anterior), e
                // a linha acaba curta/espalhada ao ser desenhada já na
                // largura nova da página seguinte.
                $this->quebrarPaginaSeNecessario($pdf, $config);
            } else {
                $palavrasLinha = $tentativa;
            }
        }

        if ($palavrasLinha !== []) {
            $this->escreverLinha($pdf, $config, $palavrasLinha, justificar: false);
        }
    }

    /**
     * Escreve uma linha já quebrada. Quando justificada, distribui o espaço
     * sobrando entre as palavras para a linha preencher toda a largura
     * disponível — útil para deixar visível, de relance, onde a largura
     * muda por causa do obstáculo (ver `larguraDisponivel()`).
     *
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $palavras
     */
    private function escreverLinha(Fpdi $pdf, array $config, array $palavras, bool $justificar): void
    {
        $largura = $this->larguraDisponivel($pdf, $config);
        $pdf->SetX($config['margin_left']);

        if (! $justificar || count($palavras) === 1) {
            $pdf->Cell($largura, $config['line_height'], $this->encode(implode(' ', $palavras)));
            $pdf->Ln($config['line_height']);

            return;
        }

        $largurasPalavras = array_map(
            fn (string $palavra) => $this->larguraTexto($pdf, $palavra),
            $palavras
        );
        $gaps = count($palavras) - 1;
        $espacoPorGap = max(($largura - array_sum($largurasPalavras)) / $gaps, 0);

        foreach ($palavras as $i => $palavra) {
            $larguraCelula = $largurasPalavras[$i] + ($i < $gaps ? $espacoPorGap : 0);
            $pdf->Cell($larguraCelula, $config['line_height'], $this->encode($palavra));
        }

        $pdf->Ln($config['line_height']);
    }

    /**
     * Quebra a página manualmente (nunca no meio de uma linha) quando a
     * próxima linha não couber mais no espaço reservado pelo rodapé.
     *
     * @param  array<string, mixed>  $config
     */
    private function quebrarPaginaSeNecessario(Fpdi $pdf, array $config): void
    {
        $limite = $pdf->GetPageHeight() - $config['bottom_margin'];

        if ($pdf->GetY() + $config['line_height'] > $limite) {
            $pdf->AddPage();
        }
    }

    private function larguraTexto(Fpdi $pdf, string $texto): float
    {
        return $pdf->GetStringWidth($this->encode($texto));
    }

    /**
     * Largura disponível para a linha na posição Y atual: reduzida enquanto
     * o cursor estiver dentro da faixa vertical da ilustração do modelo.
     *
     * @param  array<string, mixed>  $config
     */
    private function larguraDisponivel(Fpdi $pdf, array $config): float
    {
        $obstaculo = $config['obstaculo'] ?? null;
        $y = $pdf->GetY();

        if ($obstaculo && $y >= $obstaculo['top'] && $y <= $obstaculo['bottom']) {
            return $obstaculo['right_edge'] - $config['margin_left'];
        }

        return $pdf->GetPageWidth() - $config['margin_left'] - $config['margin_right'];
    }

    /**
     * @return array<int, string>
     */
    private function paragrafos(string $texto): array
    {
        $normalizado = str_replace(["\r\n", "\r"], "\n", $texto);

        return explode("\n", $normalizado);
    }

    /**
     * As fontes core do FPDF usam Windows-1252; converte o UTF-8 mantendo acentos.
     */
    private function encode(string $texto): string
    {
        return iconv('UTF-8', 'windows-1252//TRANSLIT', $texto) ?: $texto;
    }
}
