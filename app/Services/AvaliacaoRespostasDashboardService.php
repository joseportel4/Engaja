<?php

namespace App\Services;

use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AvaliacaoRespostasDashboardService
{
    /**
     * Payload idêntico ao JSON de dashboards.avaliacoes.data (totais, perguntas, recentes).
     */
    public function buildDashboardPayload(Request $request): array
    {
        $respostas = $this->filtrarRespostas($request);
        $perguntas = $this->montarPerguntas($respostas);

        $submissoesBase = $this->filtrarSubmissoes($request);
        $submissoesTable = (new SubmissaoAvaliacao)->getTable();
        $totais = [
            'submissoes' => (clone $submissoesBase)->count(),
            'atividades' => (clone $submissoesBase)->distinct('atividade_id')->count('atividade_id'),
            'eventos' => (clone $submissoesBase)
                ->leftJoin('atividades', 'atividades.id', '=', "{$submissoesTable}.atividade_id")
                ->distinct('atividades.evento_id')
                ->count('atividades.evento_id'),
            'respostas' => $respostas->count(),
            'questoes' => $perguntas->count(),
            'ultima' => optional($respostas->sortByDesc('created_at')->first())->created_at?->format('d/m/Y H:i'),
        ];

        $recentes = $respostas
            ->sortByDesc('created_at')
            ->take(8)
            ->map(function ($resposta) {
                $questao = $resposta->avaliacaoQuestao;

                return [
                    'questao' => $questao?->texto ?? 'Questão',
                    'valor' => $this->respostaParaTexto($resposta->resposta),
                    'quando' => optional($resposta->created_at)->format('d/m H:i'),
                ];
            })
            ->values();

        return [
            'totais' => $totais,
            'perguntas' => $perguntas->values()->all(),
            'recentes' => $recentes,
        ];
    }

    /**
     * Carrega respostas com colunas mínimas e relações necessárias ao agregado (menos memória que resposta_avaliacaos.*).
     */
    public function filtrarRespostas(Request $request): Collection
    {
        $respostasTable = (new RespostaAvaliacao)->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $de = $request->date('de');
        $ate = $request->date('ate');

        return RespostaAvaliacao::query()
            ->select([
                "{$respostasTable}.id",
                "{$respostasTable}.avaliacao_id",
                "{$respostasTable}.avaliacao_questao_id",
                "{$respostasTable}.submissao_avaliacao_id",
                "{$respostasTable}.resposta",
                "{$respostasTable}.created_at",
            ])
            ->with([
                'avaliacaoQuestao.escala',
                'avaliacaoQuestao.indicador.dimensao',
                'avaliacao.atividade.evento',
            ])
            ->when($templateId, fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('template_avaliacao_id', $templateId)))
            ->when($atividadeId, fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('atividade_id', $atividadeId)))
            ->when($eventoId, fn ($q) => $q->whereHas('avaliacao.atividade', fn ($aq) => $aq->where('evento_id', $eventoId)))
            ->when($de, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '<=', $ate))
            ->get();
    }

    public function filtrarSubmissoes(Request $request)
    {
        $submissoesTable = (new SubmissaoAvaliacao)->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $de = $request->date('de');
        $ate = $request->date('ate');

        return SubmissaoAvaliacao::query()
            ->when($templateId, fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('template_avaliacao_id', $templateId)))
            ->when($atividadeId, fn ($q) => $q->where('atividade_id', $atividadeId))
            ->when($eventoId, fn ($q) => $q->whereHas('atividade', fn ($aq) => $aq->where('evento_id', $eventoId)))
            ->when($de, fn ($q) => $q->whereDate("{$submissoesTable}.created_at", '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate("{$submissoesTable}.created_at", '<=', $ate));
    }

    public function montarPerguntas(Collection $respostas): Collection
    {
        $blocos = $respostas
            ->groupBy('avaliacao_questao_id')
            ->map(function ($items) {
                $questao = $items->first()->avaliacaoQuestao;
                $tipo = $questao?->tipo ?? 'texto';

                $bloco = [
                    'id' => $questao?->id ?? $items->first()->avaliacao_questao_id,
                    'texto' => $questao?->texto ?? 'Questão',
                    'tipo' => $tipo,
                    'total' => $items->count(),
                    'labels' => [],
                    'values' => [],
                    'media' => null,
                    'resumo' => null,
                    'exemplos' => [],
                    'respostas' => [],
                    'dimensao' => $questao?->indicador?->dimensao?->descricao ?? 'Sem dimensão',
                    'indicador' => $questao?->indicador?->descricao ?? 'Sem indicador',
                    'ordem' => $questao?->ordem ?? 999,
                ];

                if ($tipo === 'boolean') {
                    $sim = $items->filter(fn ($r) => $this->respostaParaBool($r->resposta) === true)->count();
                    $nao = $items->filter(fn ($r) => $this->respostaParaBool($r->resposta) === false)->count();
                    $invalidos = $items->count() - $sim - $nao;
                    $validTotal = $sim + $nao;

                    $bloco['labels'] = ['Sim', 'Nao'];
                    $bloco['values'] = [$sim, $nao];
                    if ($invalidos > 0) {
                        $bloco['labels'][] = 'Indefinido';
                        $bloco['values'][] = $invalidos;
                    }

                    if ($items->isEmpty()) {
                        $bloco['resumo'] = 'Sem respostas';
                    } elseif ($validTotal === 0) {
                        $bloco['resumo'] = 'Nenhuma resposta classificável como Sim/Não';
                    } else {
                        $bloco['resumo'] = round(($sim / $validTotal) * 100).'% de sim (entre Sim/Não)';
                        if ($invalidos > 0) {
                            $bloco['resumo'] .= '; '.$invalidos.' não classificadas';
                        }
                    }

                    return $bloco;
                }

                if ($tipo === 'escala') {
                    [$labels, $values] = $this->montarDistribuicaoOpcoes(
                        $items,
                        $questao?->escala?->valores ?? []
                    );

                    $bloco['labels'] = $labels;
                    $bloco['values'] = $values;

                    $media = $this->calcularMediaNumerica($items);
                    $bloco['media'] = $media;
                    $bloco['resumo'] = $media !== null ? 'Media '.number_format($media, 1, ',', '.') : null;

                    return $bloco;
                }

                if ($tipo === 'unica') {
                    [$labels, $values] = $this->montarDistribuicaoOpcoes(
                        $items,
                        $questao?->opcoes_resposta ?? []
                    );

                    $bloco['labels'] = $labels;
                    $bloco['values'] = $values;

                    return $bloco;
                }

                if ($tipo === 'numero') {
                    $numeros = $items->map(function ($r) {
                        $v = $this->respostaParaTexto($r->resposta);

                        return is_numeric($v) ? (float) $v : null;
                    })->filter();
                    $porValor = $numeros->groupBy(fn ($v) => (string) $v);
                    $bloco['labels'] = $porValor->keys()->values()->all();
                    $bloco['values'] = $porValor->map->count()->values()->all();
                    $media = $numeros->isEmpty() ? null : $numeros->avg();
                    $bloco['media'] = $numeros->isEmpty() ? null : round((float) $media, 2);
                    $bloco['min'] = $numeros->isEmpty() ? null : $numeros->min();
                    $bloco['max'] = $numeros->isEmpty() ? null : $numeros->max();
                    $bloco['resumo'] = $numeros->isEmpty()
                        ? null
                        : 'Media '.number_format((float) $media, 2, ',', '.');

                    return $bloco;
                }

                $respostasTexto = $items
                    ->sortByDesc('created_at')
                    ->map(fn ($r) => $this->respostaParaTexto($r->resposta))
                    ->filter()
                    ->values();

                $maxTexto = 250;
                $totalTexto = $respostasTexto->count();
                $bloco['respostas'] = $respostasTexto->take($maxTexto)->values()->all();
                $bloco['respostas_total'] = $totalTexto;
                $bloco['respostas_truncadas'] = $totalTexto > $maxTexto;
                $bloco['exemplos'] = $respostasTexto->take(5)->values()->all();

                return $bloco;
            });

        return $blocos->values()->sortBy(function ($p) {
            $dim = mb_strtolower($p['dimensao'] ?? '');
            $ind = mb_strtolower($p['indicador'] ?? '');
            $ordem = $p['ordem'] ?? 999;
            $id = $p['id'] ?? 0;

            return sprintf('%s|%s|%03d|%06d', $dim, $ind, $ordem, $id);
        })->values();
    }

    private function montarDistribuicaoOpcoes(Collection $items, array $opcoesConfiguradas = []): array
    {
        $opcoes = collect($opcoesConfiguradas)
            ->map(fn ($opcao) => is_string($opcao) ? trim($opcao) : '')
            ->filter()
            ->values()
            ->all();

        if (empty($opcoes)) {
            foreach ($items as $resposta) {
                $valor = $this->respostaParaTexto($resposta->resposta);
                if ($valor !== '' && ! in_array($valor, $opcoes, true)) {
                    $opcoes[] = $valor;
                }
            }
        }

        $contagem = [];
        foreach ($opcoes as $opcao) {
            $contagem[$opcao] = 0;
        }

        foreach ($items as $resposta) {
            $valor = $this->respostaParaTexto($resposta->resposta);
            if ($valor === '') {
                continue;
            }

            $contagem[$valor] = ($contagem[$valor] ?? 0) + 1;
        }

        $labelsOrdenadas = [];
        $valuesOrdenadas = [];

        foreach ($opcoes as $opcao) {
            if (array_key_exists($opcao, $contagem)) {
                $labelsOrdenadas[] = $opcao;
                $valuesOrdenadas[] = $contagem[$opcao];
            }
        }

        foreach ($contagem as $opcao => $total) {
            if (! in_array($opcao, $opcoes, true)) {
                $labelsOrdenadas[] = $opcao;
                $valuesOrdenadas[] = $total;
            }
        }

        return [$labelsOrdenadas, $valuesOrdenadas];
    }

    public function respostaParaTexto($valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_array($valor)) {
            return implode(', ', $valor);
        }

        $texto = (string) $valor;

        if ($texto === '') {
            return '';
        }

        if (in_array(substr($texto, 0, 1), ['[', '{'], true)) {
            $decoded = json_decode($texto, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return implode(', ', $decoded);
            }
        }

        return $texto;
    }

    public function respostaParaBool($valor): ?bool
    {
        $texto = strtolower(trim($this->respostaParaTexto($valor)));

        if ($texto === '') {
            return null;
        }

        if (in_array($texto, ['1', 'true', 'sim', 's', 'yes'], true)) {
            return true;
        }

        if (in_array($texto, ['0', 'false', 'nao', 'n', 'no'], true)) {
            return false;
        }

        return null;
    }

    public function calcularMediaNumerica($items): ?float
    {
        $numeros = $items->map(function ($resposta) {
            $valor = $this->respostaParaTexto($resposta->resposta);

            return is_numeric($valor) ? (float) $valor : null;
        })->filter();

        if ($numeros->isEmpty()) {
            return null;
        }

        return round((float) $numeros->avg(), 1);
    }
}
