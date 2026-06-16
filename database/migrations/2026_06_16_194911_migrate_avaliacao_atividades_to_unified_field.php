<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Certifica-se que a coluna existe antes de migrar os dados
        if (! Schema::hasColumn('avaliacao_atividades', 'questao_unificada')) {
            Schema::table('avaliacao_atividades', function (Blueprint $table) {
                $table->text('questao_unificada')->nullable()->after('avaliacao_atuacao_equipe');
            });
        }

        DB::transaction(function () {
            $mapeamento = [
                'avaliacao_logistica'          => 'Avaliação da Logística',
                'avaliacao_acolhimento_sme'    => 'Avaliação do acolhimento e apoio da SME',
                'avaliacao_atuacao_equipe'     => 'Atuação da Equipe do IPF',
                'avaliacao_planejamento'       => 'Desenvolvimento da Ação (Planejamento)',
                'avaliacao_recursos_materiais' => 'Recursos Materiais',
                'avaliacao_links_presenca'     => 'Links e QR codes',
                'avaliacao_destaques'          => 'Destaques',
            ];

            DB::table('avaliacao_atividades')->orderBy('id')->chunk(100, function ($registros) use ($mapeamento) {
                foreach ($registros as $reg) {
                    $partes = [];
                    foreach ($mapeamento as $campo => $label) {
                        if (filled($reg->$campo)) {
                            $partes[] = $label . ': ' . $reg->$campo;
                        }
                    }

                    if (! empty($partes)) {
                        DB::table('avaliacao_atividades')
                            ->where('id', $reg->id)
                            ->update(['questao_unificada' => implode(' ; ', $partes)]);
                    }
                }
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('avaliacao_atividades')->update(['questao_unificada' => null]);
    }
};
