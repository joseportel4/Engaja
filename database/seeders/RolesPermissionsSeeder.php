<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissoes = [
            'user.ver','user.criar','user.editar','user.excluir',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
            'evento.ver','evento.criar','evento.editar','evento.excluir',
            'inscricao.ver','inscricao.criar','inscricao.editar','inscricao.excluir',
            'presenca.registrar', 'relatorio.ver',
        ];

        foreach ($permissoes as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $admin        = Role::firstOrCreate(['name' => 'administrador', 'guard_name' => 'web']);
        $gestor       = Role::firstOrCreate(['name' => 'gestor', 'guard_name' => 'web']);
        $formador     = Role::firstOrCreate(['name' => 'formador', 'guard_name' => 'web']);
        $participante = Role::firstOrCreate(['name' => 'participante', 'guard_name' => 'web']);

        // ADMIN: adm de usuários
        $admin->syncPermissions([
            'user.ver','user.criar','user.editar','user.excluir',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
        ]);

        // GESTOR: acesso aos relatórios
        $gestor->syncPermissions([
            'relatorio.ver',
        ]);

        // FORMADOR: cadastro e gerenciamento dos eventos
        $formador->syncPermissions([
            'evento.ver','evento.criar','evento.editar','evento.excluir',
            'participante.ver','participante.criar','participante.editar','participante.excluir',
            'inscricao.ver','inscricao.criar','inscricao.editar','inscricao.excluir',
            'presenca.registrar', 
        ]);

        // PARTICIPANTE: consumo e ações do próprio contexto
        $participante->syncPermissions([
            'evento.ver',
            'inscricao.ver','inscricao.criar',
        ]);
    }
}
