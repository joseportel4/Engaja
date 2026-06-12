<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->index('avaliacao_id');
            $table->index('avaliacao_questao_id');
            $table->index('created_at');
        });

        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->index('atividade_id');
            $table->index('avaliacao_id');
            $table->index('universal');
        });
    }

    public function down(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropIndex(['avaliacao_id']);
            $table->dropIndex(['avaliacao_questao_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->dropIndex(['atividade_id']);
            $table->dropIndex(['avaliacao_id']);
            $table->dropIndex(['universal']);
        });
    }
};
