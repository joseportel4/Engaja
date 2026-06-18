<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->text('questao_unificada')->nullable()->after('avaliacao_atuacao_equipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->dropColumn('questao_unificada');
        });
    }
};
