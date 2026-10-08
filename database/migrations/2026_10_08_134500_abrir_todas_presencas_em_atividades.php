<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Abre a confirmação de presença para todos os momentos pedagógicos (atividades),
     * definindo presenca_ativa = true e limpando agendamentos que fechariam ou retardariam a abertura.
     */
    public function up(): void
    {
        DB::table('atividades')->update([
            'presenca_ativa' => true,
            'presenca_abre_em' => null,
            'presenca_fecha_em' => null,
        ]);
    }

    /**
     * Reverte fechando a confirmação de presença em todas as atividades.
     */
    public function down(): void
    {
        DB::table('atividades')->update([
            'presenca_ativa' => false,
            'presenca_abre_em' => null,
            'presenca_fecha_em' => null,
        ]);
    }
};
