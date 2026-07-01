<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    public function up(): void
    {
        DB::table('avaliacao_atividades')
            ->whereNotNull('questao_unificada')
            ->orderBy('id')
            ->chunk(100, function ($registros) {
                foreach ($registros as $reg) {
                    $texto = $reg->questao_unificada;

                    if ($this->contemHtml($texto)) {
                        continue;
                    }

                    $html = $this->converterParaHtml($texto);

                    if ($html !== null) {
                        DB::table('avaliacao_atividades')
                            ->where('id', $reg->id)
                            ->update(['questao_unificada' => $html]);
                    }
                }
            });
    }

 
    public function down(): void
    {

    }

  
    private function contemHtml(string $texto): bool
    {
        return $texto !== strip_tags($texto);
    }


    private function converterParaHtml(string $texto): ?string
    {
        $texto = trim($texto);

        if ($texto === '') {
            return null;
        }

        $partes = preg_split('/\s*;\s*/', $texto);

        if (empty($partes)) {
            return null;
        }

        $paragrafos = [];

        foreach ($partes as $parte) {
            $parte = trim($parte);

            if ($parte === '') {
                continue;
            }

            if (preg_match('/^([^:]+):\s*(.*)$/su', $parte, $matches)) {
                $label   = htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $conteudo = htmlspecialchars(trim($matches[2]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                if ($conteudo !== '') {
                    $paragrafos[] = "<p><strong>{$label}:</strong> {$conteudo}</p>";
                } else {
                    $paragrafos[] = "<p><strong>{$label}</strong></p>";
                }
            } else {
                $parteEscapada = htmlspecialchars($parte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $paragrafos[] = "<p>{$parteEscapada}</p>";
            }
        }

        return empty($paragrafos) ? null : implode('', $paragrafos);
    }
};
