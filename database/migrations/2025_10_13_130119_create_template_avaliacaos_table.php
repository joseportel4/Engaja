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
        Schema::create('template_avaliacaos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::create('questao_template_avaliacaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_avaliacao_id')
                ->constrained('template_avaliacaos')
                ->cascadeOnDelete();
            $table->foreignId('questao_id')
                ->constrained('questaos')
                ->cascadeOnDelete();
            $table->integer('ordem')->nullable();
            $table->unique(['template_avaliacao_id', 'questao_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questao_template_avaliacaos');
        Schema::dropIfExists('template_avaliacaos');
    }
};
