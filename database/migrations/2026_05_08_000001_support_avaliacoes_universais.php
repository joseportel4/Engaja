<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE avaliacaos ALTER COLUMN atividade_id DROP NOT NULL');
        DB::statement('ALTER TABLE submissao_avaliacoes ALTER COLUMN atividade_id DROP NOT NULL');

        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->boolean('universal')->default(false)->after('presenca_id');
        });
    }

    public function down(): void
    {
        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->dropColumn('universal');
        });

        DB::statement('ALTER TABLE submissao_avaliacoes ALTER COLUMN atividade_id SET NOT NULL');
        DB::statement('ALTER TABLE avaliacaos ALTER COLUMN atividade_id SET NOT NULL');
    }
};
