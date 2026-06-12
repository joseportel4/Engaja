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
        Schema::table('presencas', function (Blueprint $table) {
            $table->index(['atividade_id', 'status']);
            $table->index(['atividade_id', 'deleted_at']);
        });

        Schema::table('inscricaos', function (Blueprint $table) {
            $table->index(['atividade_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('presencas', function (Blueprint $table) {
            $table->dropIndex(['atividade_id', 'status']);
            $table->dropIndex(['atividade_id', 'deleted_at']);
        });

        Schema::table('inscricaos', function (Blueprint $table) {
            $table->dropIndex(['atividade_id', 'deleted_at']);
        });
    }
};
