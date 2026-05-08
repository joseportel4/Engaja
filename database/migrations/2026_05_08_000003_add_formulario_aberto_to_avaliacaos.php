<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacaos', function (Blueprint $table) {
            $table->boolean('formulario_aberto')->default(true)->after('descricao_universal');
        });
    }

    public function down(): void
    {
        Schema::table('avaliacaos', function (Blueprint $table) {
            $table->dropColumn('formulario_aberto');
        });
    }
};
