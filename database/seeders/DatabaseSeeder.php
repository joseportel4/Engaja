<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            EixoSeeder::class,
            RegiaoSeeder::class,
            EstadoSeeder::class,
            MunicipioSeeder::class,
            RolesPermissionsSeeder::class,
        ]);

        $administrador = User::factory()->create([
            'name'  => 'Admin Engaja',
            'email' => 'admin@engaja.local',
        ]);

        $administrador->assignRole('administrador');
    }
}
