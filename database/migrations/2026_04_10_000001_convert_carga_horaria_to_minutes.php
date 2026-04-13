<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Valores históricos de carga_horaria estavam em horas inteiras; passam a representar minutos.
     */
    public function up(): void
    {
        DB::table('atividades')
            ->whereNotNull('carga_horaria')
            ->update([
                'carga_horaria' => DB::raw('carga_horaria * 60'),
            ]);

        DB::table('certificados')
            ->whereNotNull('carga_horaria')
            ->update([
                'carga_horaria' => DB::raw('carga_horaria * 60'),
            ]);
    }

    public function down(): void
    {
        DB::table('atividades')
            ->whereNotNull('carga_horaria')
            ->update([
                'carga_horaria' => DB::raw('FLOOR(carga_horaria / 60)'),
            ]);

        DB::table('certificados')
            ->whereNotNull('carga_horaria')
            ->update([
                'carga_horaria' => DB::raw('FLOOR(carga_horaria / 60)'),
            ]);
    }
};
