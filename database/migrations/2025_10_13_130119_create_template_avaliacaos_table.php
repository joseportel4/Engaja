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
        Schema::create('questao_template_avaliacaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_avaliacao_id')->constrained('template_avaliacaos');
            $table->foreignId('questao_id')->constrained('questaos');
            $table->integer('ordem')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_avaliacaos');
    }
};
