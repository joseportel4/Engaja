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
        Schema::create('atividade_acoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('detalhe')->nullable();
            $table->boolean('usa_turmas')->default(false);
            $table->json('turmas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_acoes');
    }
};
