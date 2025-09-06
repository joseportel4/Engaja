<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegiaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $regioes = [
            ['nome' => 'Norte', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Nordeste I','created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Nordeste II','created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('regiaos')->insert($regioes);
    }
}
