<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;


class MunicipioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $municipios = [
            //Região Norte
            ['nome' => 'Oiapoque',                 'estado_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Carauari',                 'estado_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Belém',                    'estado_id' => 3, 'created_at' => $now, 'updated_at' => $now],

            //Região Nordeste I
            ['nome' => 'Caucaia',                  'estado_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Fortaleza',                'estado_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Icapuí',                   'estado_id' => 4, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Alto do Rodrigues',        'estado_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Porto do Mangue',          'estado_id' => 5, 'created_at' => $now, 'updated_at' => $now],
            
            //Região Nordeste II
            ['nome' => 'Araçás',                   'estado_id' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'São Francisco do Conde',   'estado_id' => 6, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Conde',                    'estado_id' => 7, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Ipojuca',                  'estado_id' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Cabo de Santo Agostinho',  'estado_id' => 8, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Brejo Grande',             'estado_id' => 9, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Santa Luzia de Itanhy',    'estado_id' => 9, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('municipios')->insert($municipios);
    }
}
