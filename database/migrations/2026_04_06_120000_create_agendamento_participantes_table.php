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
        Schema::create('agendamento_participantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->cascadeOnDelete();
            $table->string('nome');
            $table->string('cpf')->nullable();
            $table->string('email')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->string('telefone')->nullable();
            $table->string('sexo')->nullable();
            $table->string('vinculo')->nullable();
            $table->string('turma')->nullable();
            $table->string('origem')->default('manual');
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agendamento_id', 'nome']);
            $table->index(['agendamento_id', 'email']);
            $table->index(['agendamento_id', 'cpf']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamento_participantes');
    }
};

