<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            //adiciona o campo após a coluna tag, com false como padrao (nao autorizou)
            $table->boolean('autorizacao_imagem')->default(false)->after('tag');
        });
    }

    public function down(): void
    {
        Schema::table('participantes', function (Blueprint $table) {
            $table->dropColumn('autorizacao_imagem');
        });
    }
};
