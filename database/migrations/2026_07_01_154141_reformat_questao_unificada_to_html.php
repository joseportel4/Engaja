<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        $colunasAntigas = [
            'avaliacao_logistica',
            'avaliacao_acolhimento_sme',
            'avaliacao_atuacao_equipe',
            'avaliacao_planejamento',
            'avaliacao_recursos_materiais',
            'avaliacao_links_presenca',
            'avaliacao_destaques',
        ];

        // Descobre quais colunas antigas ainda existem na tabela
        $existentes = array_filter(
            $colunasAntigas,
            fn ($col) => Schema::hasColumn('avaliacao_atividades', $col)
        );

        DB::table('avaliacao_atividades')
            ->whereNotNull('questao_unificada')
            ->whereDate('updated_at', '=', '2026-06-17')
            ->when(! empty($existentes), function ($query) use ($existentes) {
                $query->where(function ($q) use ($existentes) {
                    foreach ($existentes as $col) {
                        $q->orWhereNotNull($col);
                    }
                });
            })
            ->orderBy('id')
            ->chunk(100, function ($registros) {
                foreach ($registros as $reg) {
                    $texto = $reg->questao_unificada;

                    // Pula registros que já contêm HTML
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

    /**
     * Não há rollback seguro sem backup do banco.
     */
    public function down(): void
    {
        // Rollback não implementado intencionalmente.
    }

    /**
     * Verifica se o texto já contém tags HTML.
     */
    private function contemHtml(string $texto): bool
    {
        return $texto !== strip_tags($texto);
    }

    /**
     * Converte o formato legado "Label: texto ; Label2: texto2" para HTML.
     *
     * Produz:
     *   <p><strong>Label:</strong> texto</p>
     *   <p><strong>Label2:</strong> texto2</p>
     */
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

            // Padrão "Label: valor"
            if (preg_match('/^([^:]+):\s*(.*)$/su', $parte, $matches)) {
                $label    = htmlspecialchars(trim($matches[1]), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
