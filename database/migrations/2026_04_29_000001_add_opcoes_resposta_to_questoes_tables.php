<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questaos', function (Blueprint $table) {
            $table->json('opcoes_resposta')->nullable()->after('tipo');
        });

        Schema::table('avaliacao_questoes', function (Blueprint $table) {
            $table->json('opcoes_resposta')->nullable()->after('tipo');
        });
    }

    public function down(): void
    {
        Schema::table('avaliacao_questoes', function (Blueprint $table) {
            $table->dropColumn('opcoes_resposta');
        });

        Schema::table('questaos', function (Blueprint $table) {
            $table->dropColumn('opcoes_resposta');
        });
    }
};
