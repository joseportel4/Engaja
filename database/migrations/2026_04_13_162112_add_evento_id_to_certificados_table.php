<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            //nullable para não quebrar certificados antigos que já existem no banco.
            //nullOnDelete() garante que, se o evento for excluído, o certificado não seja apagado junto
            $table->foreignId('evento_id')
                ->nullable()
                ->after('participante_id')
                ->constrained('eventos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropForeign(['evento_id']);
            $table->dropColumn('evento_id');
        });
    }
};
