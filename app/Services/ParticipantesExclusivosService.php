<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ParticipantesExclusivosService
{
    /**
     * Participantes com inscricao ou presenca presente nas acoes selecionadas,
     * sem inscricao ou presenca presente em qualquer outra acao.
     *
     * @param  array<int>  $eventoIds
     */
    public function query(array $eventoIds): Builder
    {
        $eventoIds = $this->normalizarIds($eventoIds);

        return DB::table('participantes')
            ->leftJoin('users', 'users.id', '=', 'participantes.user_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'participantes.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->whereNull('participantes.deleted_at')
            ->where(function (Builder $query) use ($eventoIds) {
                $query
                    ->whereExists(fn (Builder $sub) => $this->subqueryInscricaoSelecionada($sub, $eventoIds))
                    ->orWhereExists(fn (Builder $sub) => $this->subqueryPresencaSelecionada($sub, $eventoIds));
            })
            ->whereNotExists(fn (Builder $sub) => $this->subqueryInscricaoForaDaSelecao($sub, $eventoIds))
            ->whereNotExists(fn (Builder $sub) => $this->subqueryPresencaForaDaSelecao($sub, $eventoIds))
            ->select([
                'participantes.id as participante_id',
                'users.name as nome',
                'users.email',
                'participantes.cpf',
                'participantes.telefone',
                'municipios.nome as municipio',
                'estados.sigla as estado',
                'participantes.escola_unidade',
                'participantes.tipo_organizacao',
                'participantes.tag',
                'participantes.autorizacao_imagem',
            ])
            ->selectSub(fn (Builder $sub) => $this->subqueryContarInscricoesSelecionadas($sub, $eventoIds), 'inscricoes_selecionadas')
            ->selectSub(fn (Builder $sub) => $this->subqueryContarPresencasSelecionadas($sub, $eventoIds), 'presencas_selecionadas')
            ->orderBy('users.name');
    }

    /**
     * @param  array<int|string>  $ids
     * @return array<int>
     */
    public function normalizarIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function subqueryInscricaoSelecionada(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('inscricaos as i_sel')
            ->leftJoin('eventos as e_sel', 'e_sel.id', '=', 'i_sel.evento_id')
            ->leftJoin('atividades as a_sel', 'a_sel.id', '=', 'i_sel.atividade_id')
            ->leftJoin('eventos as ae_sel', 'ae_sel.id', '=', 'a_sel.evento_id')
            ->whereColumn('i_sel.participante_id', 'participantes.id')
            ->whereNull('i_sel.deleted_at')
            ->where(function (Builder $q) use ($eventoIds) {
                $q->where(function (Builder $direct) use ($eventoIds) {
                    $direct->whereIn('e_sel.id', $eventoIds)
                        ->whereNull('e_sel.deleted_at');
                })->orWhere(function (Builder $byActivity) use ($eventoIds) {
                    $byActivity->whereIn('ae_sel.id', $eventoIds)
                        ->whereNull('a_sel.deleted_at')
                        ->whereNull('ae_sel.deleted_at');
                });
            })
            ->selectRaw('1');
    }

    private function subqueryPresencaSelecionada(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('presencas as p_sel')
            ->join('inscricaos as ip_sel', 'ip_sel.id', '=', 'p_sel.inscricao_id')
            ->join('atividades as ap_sel', 'ap_sel.id', '=', 'p_sel.atividade_id')
            ->join('eventos as ep_sel', 'ep_sel.id', '=', 'ap_sel.evento_id')
            ->whereColumn('ip_sel.participante_id', 'participantes.id')
            ->where(function (Builder $q) {
                $q->where('p_sel.status', 'presente')
                    ->orWhere('ip_sel.ouvinte', true);
            })
            ->whereIn('ep_sel.id', $eventoIds)
            ->whereNull('p_sel.deleted_at')
            ->whereNull('ip_sel.deleted_at')
            ->whereNull('ap_sel.deleted_at')
            ->whereNull('ep_sel.deleted_at')
            ->selectRaw('1');
    }

    private function subqueryInscricaoForaDaSelecao(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('inscricaos as i_out')
            ->leftJoin('eventos as e_out', 'e_out.id', '=', 'i_out.evento_id')
            ->leftJoin('atividades as a_out', 'a_out.id', '=', 'i_out.atividade_id')
            ->leftJoin('eventos as ae_out', 'ae_out.id', '=', 'a_out.evento_id')
            ->whereColumn('i_out.participante_id', 'participantes.id')
            ->whereNull('i_out.deleted_at')
            ->where(function (Builder $q) use ($eventoIds) {
                $q->where(function (Builder $direct) use ($eventoIds) {
                    $direct->whereNotNull('e_out.id')
                        ->whereNotIn('e_out.id', $eventoIds)
                        ->whereNull('e_out.deleted_at');
                })->orWhere(function (Builder $nested) use ($eventoIds) {
                    $nested->whereNotNull('ae_out.id')
                        ->whereNotIn('ae_out.id', $eventoIds)
                        ->whereNull('a_out.deleted_at')
                        ->whereNull('ae_out.deleted_at');
                    });
            })
            ->selectRaw('1');
    }

    private function subqueryPresencaForaDaSelecao(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('presencas as p_out')
            ->join('inscricaos as ip_out', 'ip_out.id', '=', 'p_out.inscricao_id')
            ->join('atividades as ap_out', 'ap_out.id', '=', 'p_out.atividade_id')
            ->join('eventos as ep_out', 'ep_out.id', '=', 'ap_out.evento_id')
            ->whereColumn('ip_out.participante_id', 'participantes.id')
            ->where(function (Builder $q) {
                $q->where('p_out.status', 'presente')
                    ->orWhere('ip_out.ouvinte', true);
            })
            ->whereNotIn('ep_out.id', $eventoIds)
            ->whereNull('p_out.deleted_at')
            ->whereNull('ip_out.deleted_at')
            ->whereNull('ap_out.deleted_at')
            ->whereNull('ep_out.deleted_at')
            ->selectRaw('1');
    }

    private function subqueryContarInscricoesSelecionadas(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('inscricaos as i_count')
            ->leftJoin('eventos as e_count', 'e_count.id', '=', 'i_count.evento_id')
            ->leftJoin('atividades as a_count', 'a_count.id', '=', 'i_count.atividade_id')
            ->leftJoin('eventos as ae_count', 'ae_count.id', '=', 'a_count.evento_id')
            ->whereColumn('i_count.participante_id', 'participantes.id')
            ->whereNull('i_count.deleted_at')
            ->where(function (Builder $q) use ($eventoIds) {
                $q->where(function (Builder $direct) use ($eventoIds) {
                    $direct->whereIn('e_count.id', $eventoIds)
                        ->whereNull('e_count.deleted_at');
                })->orWhere(function (Builder $byActivity) use ($eventoIds) {
                    $byActivity->whereIn('ae_count.id', $eventoIds)
                        ->whereNull('a_count.deleted_at')
                        ->whereNull('ae_count.deleted_at');
                });
            })
            ->selectRaw('count(distinct i_count.id)');
    }

    private function subqueryContarPresencasSelecionadas(Builder $sub, array $eventoIds): Builder
    {
        return $sub
            ->from('presencas as p_count')
            ->join('inscricaos as ip_count', 'ip_count.id', '=', 'p_count.inscricao_id')
            ->join('atividades as ap_count', 'ap_count.id', '=', 'p_count.atividade_id')
            ->join('eventos as ep_count', 'ep_count.id', '=', 'ap_count.evento_id')
            ->whereColumn('ip_count.participante_id', 'participantes.id')
            ->where(function (Builder $q) {
                $q->where('p_count.status', 'presente')
                    ->orWhere('ip_count.ouvinte', true);
            })
            ->whereIn('ep_count.id', $eventoIds)
            ->whereNull('p_count.deleted_at')
            ->whereNull('ip_count.deleted_at')
            ->whereNull('ap_count.deleted_at')
            ->whereNull('ep_count.deleted_at')
            ->selectRaw('count(distinct p_count.id)');
    }
}
