<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsuariosSemVinculoService
{
    private const PROTECTED_ROLES = ['administrador'];

    public function query(User $viewer): Builder
    {
        return User::query()
            ->with(['roles', 'participante.municipio.estado'])
            ->when(! $viewer->hasRole('administrador'), function (Builder $query) {
                $query->whereDoesntHave('roles', function (Builder $sub) {
                    $sub->whereIn('name', self::PROTECTED_ROLES);
                });
            })
            ->where(function (Builder $query) {
                $query
                    ->whereDoesntHave('participante')
                    ->orWhereHas('participante', function (Builder $participanteQuery) {
                        $participanteQuery
                            ->whereDoesntHave('inscricoes', function (Builder $inscricaoQuery) {
                                $inscricaoQuery->where(function (Builder $vinculoQuery) {
                                    $vinculoQuery
                                        ->whereHas('evento')
                                        ->orWhereHas('atividade.evento');
                                });
                            })
                            ->whereDoesntHave('inscricoes.presencas', function (Builder $presencaQuery) {
                                $presencaQuery->whereHas('atividade.evento');
                            });
                    });
            })
            ->orderBy('name');
    }
}
