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
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->boolean('efetivado')->default(false)->after('local_acao');
            $table->timestamp('efetivado_em')->nullable()->after('efetivado');
            $table->foreignId('atividade_id')
                ->nullable()
                ->after('efetivado_em')
                ->constrained('atividades')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agendamentos', function (Blueprint $table) {
            $table->dropForeign(['atividade_id']);
            $table->dropColumn(['efetivado', 'efetivado_em', 'atividade_id']);
        });
    }
};
