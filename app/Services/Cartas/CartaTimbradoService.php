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

    private function render(string $texto): string
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

        $pdf->SetAutoPageBreak(true, $config['bottom_margin']);
        $pdf->SetMargins($config['margin_left'], $config['start_top'], $config['margin_right']);
        $pdf->AddPage();
        $pdf->SetXY($config['margin_left'], $config['start_top']);
        $pdf->SetFont($config['font_family'], '', $config['font_size']);
        $pdf->SetTextColor(45, 45, 45);

        $largura = $pdf->GetPageWidth() - $config['margin_left'] - $config['margin_right'];

        foreach ($this->paragrafos($texto) as $paragrafo) {
            if ($paragrafo === '') {
                $pdf->Ln($config['line_height']);

                continue;
            }

            $pdf->MultiCell($largura, $config['line_height'], $this->encode($paragrafo));
        }

        return $pdf->Output('S');
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
