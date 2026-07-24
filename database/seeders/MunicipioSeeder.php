<?php

namespace Database\Seeders;

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
            // Região Norte
            ['nome' => 'Oiapoque',                 'uf' => 'AP'],
            ['nome' => 'Coari',                    'uf' => 'AM'],
            ['nome' => 'Carauari',                 'uf' => 'AM'],
            ['nome' => 'Belém',                    'uf' => 'PA'],

            // Região Nordeste I
            ['nome' => 'Caucaia',                  'uf' => 'CE'],
            ['nome' => 'Fortaleza',                'uf' => 'CE'],
            ['nome' => 'Icapuí',                   'uf' => 'CE'],
            ['nome' => 'Alto do Rodrigues',        'uf' => 'RN'],
            ['nome' => 'Porto do Mangue',          'uf' => 'RN'],

            // Região Nordeste II
            ['nome' => 'Araçás',                   'uf' => 'BA'],
            ['nome' => 'São Francisco do Conde',   'uf' => 'BA'],
            ['nome' => 'Conde',                    'uf' => 'PB'],
            ['nome' => 'Ipojuca',                  'uf' => 'PE'],
            ['nome' => 'Cabo de Santo Agostinho',  'uf' => 'PE'],
            ['nome' => 'Brejo Grande',             'uf' => 'SE'],
            ['nome' => 'Santa Luzia do Itanhy',    'uf' => 'SE'],
        ];

        $estadoIds = DB::table('estados')->pluck('id', 'sigla');

        foreach ($municipios as $municipio) {
            DB::table('municipios')->updateOrInsert(
                [
                    'estado_id' => $estadoIds[$municipio['uf']],
                    'nome' => $municipio['nome'],
                ],
                [
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
