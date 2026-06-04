<?php

namespace App\Services;

use App\Models\AvaliacaoQuestao;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AvaliacaoRespostasDashboardService
{
    /**
     * Payload idêntico ao JSON de dashboards.avaliacoes.data (totais, perguntas, recentes).
     * Suporta paginação de perguntas via ?page=1&per_page=20.
     */
    public function buildDashboardPayload(Request $request): array
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = min(max((int) $request->query('per_page', 50), 1), 100);

        $agregados = $this->filtrarRespostasAgregadas($request);
        $questaoIds = $agregados->pluck('avaliacao_questao_id')->unique()->filter()->values();

        $questoes = AvaliacaoQuestao::with(['escala', 'indicador.dimensao'])
            ->whereIn('id', $questaoIds)
            ->get()
            ->keyBy('id');

        $perguntas = $this->montarPerguntas($agregados, $questoes);

        $submissoesBase = $this->filtrarSubmissoes($request);
        $submissoesTable = (new SubmissaoAvaliacao)->getTable();
        $tipo = $this->tipoRequest($request);
        $isUniversal = $tipo === 'universal';

        $totalRespostas = $agregados->sum('count');
        $ultimaResposta = $agregados->max('ultima');

        $totais = [
            'submissoes' => (clone $submissoesBase)->count(),
            'atividades' => (clone $submissoesBase)->distinct('atividade_id')->count('atividade_id'),
            'eventos' => $isUniversal
                ? (clone $submissoesBase)->distinct('avaliacao_id')->count('avaliacao_id')
                : (clone $submissoesBase)
                    ->leftJoin('atividades', 'atividades.id', '=', "{$submissoesTable}.atividade_id")
                    ->distinct('atividades.evento_id')
                    ->count('atividades.evento_id'),
            'respostas' => $totalRespostas,
            'questoes' => $perguntas->count(),
            'ultima' => $ultimaResposta ? Carbon::parse($ultimaResposta)->format('d/m/Y H:i') : null,
            'modo' => $tipo,
        ];

        $recentes = $this->buscarRecentes($request);

        $totalPerguntas = $perguntas->count();
        $perguntasPaginadas = $perguntas
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        return [
            'totais' => $totais,
            'perguntas' => $perguntasPaginadas->all(),
            'recentes' => $recentes,
            'meta' => [
                'total' => $totalPerguntas,
                'page' => $page,
                'per_page' => $perPage,
                'last_page' => (int) ceil($totalPerguntas / $perPage),
            ],
        ];
    }

    /**
     * Carrega respostas agregadas por (avaliacao_questao_id, resposta) usando JOIN em vez de whereHas.
     * Reduz drasticamente o número de linhas retornadas ao banco em vez de uma linha por resposta individual.
     */
    public function filtrarRespostasAgregadas(Request $request): Collection
    {
        $respostasTable = (new RespostaAvaliacao)->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $avaliacaoId = $request->integer('avaliacao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $tipo = $this->tipoRequest($request);
        $isUniversal = $tipo === 'universal';

        $query = RespostaAvaliacao::query()
            ->select([
                DB::raw("{$respostasTable}.avaliacao_questao_id"),
                DB::raw("{$respostasTable}.resposta"),
                DB::raw('COUNT(*) as count'),
                DB::raw("MAX({$respostasTable}.created_at) as ultima"),
            ])
            ->join('avaliacaos', 'avaliacaos.id', '=', "{$respostasTable}.avaliacao_id")
            ->when($isUniversal, fn ($q) => $q->whereNull('avaliacaos.atividade_id'))
            ->when(! $isUniversal, fn ($q) => $q->whereNotNull('avaliacaos.atividade_id'))
            ->when($tipo === 'transcricao', fn ($q) => $q->where('avaliacaos.transcricao', true))
            ->when($tipo === 'momento', fn ($q) => $q->where('avaliacaos.transcricao', false))
            ->when($templateId, fn ($q) => $q->where('avaliacaos.template_avaliacao_id', $templateId))
            ->when($isUniversal && $avaliacaoId, fn ($q) => $q->where("{$respostasTable}.avaliacao_id", $avaliacaoId))
            ->when($atividadeId, fn ($q) => $q->where('avaliacaos.atividade_id', $atividadeId))
            ->when(! $isUniversal && $eventoId, function ($q) use ($eventoId) {
                $q->join('atividades', 'atividades.id', '=', 'avaliacaos.atividade_id')
                    ->where('atividades.evento_id', $eventoId);
            })
            ->when($de, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '<=', $ate))
            ->groupBy("{$respostasTable}.avaliacao_questao_id", "{$respostasTable}.resposta");

        return $query->get();
    }

    /**
     * Busca os 8 registros mais recentes sem carregar todo o dataset.
     */
    private function buscarRecentes(Request $request): Collection
    {
        $respostasTable = (new RespostaAvaliacao)->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $avaliacaoId = $request->integer('avaliacao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $tipo = $this->tipoRequest($request);
        $isUniversal = $tipo === 'universal';

        return RespostaAvaliacao::query()
            ->select([
                "{$respostasTable}.avaliacao_questao_id",
                "{$respostasTable}.resposta",
                "{$respostasTable}.created_at",
            ])
            ->join('avaliacaos', 'avaliacaos.id', '=', "{$respostasTable}.avaliacao_id")
            ->when($isUniversal, fn ($q) => $q->whereNull('avaliacaos.atividade_id'))
            ->when(! $isUniversal, fn ($q) => $q->whereNotNull('avaliacaos.atividade_id'))
            ->when($tipo === 'transcricao', fn ($q) => $q->where('avaliacaos.transcricao', true))
            ->when($tipo === 'momento', fn ($q) => $q->where('avaliacaos.transcricao', false))
            ->when($templateId, fn ($q) => $q->where('avaliacaos.template_avaliacao_id', $templateId))
            ->when($isUniversal && $avaliacaoId, fn ($q) => $q->where("{$respostasTable}.avaliacao_id", $avaliacaoId))
            ->when($atividadeId, fn ($q) => $q->where('avaliacaos.atividade_id', $atividadeId))
            ->when(! $isUniversal && $eventoId, function ($q) use ($eventoId) {
                $q->join('atividades', 'atividades.id', '=', 'avaliacaos.atividade_id')
                    ->where('atividades.evento_id', $eventoId);
            })
            ->when($de, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate("{$respostasTable}.created_at", '<=', $ate))
            ->orderByDesc("{$respostasTable}.created_at")
            ->limit(8)
            ->get()
            ->map(function ($resposta) {
                $questaoId = $resposta->avaliacao_questao_id;

                return [
                    'questao' => AvaliacaoQuestao::find($questaoId)?->texto ?? 'Questão',
                    'valor' => $this->respostaParaTexto($resposta->resposta),
                    'quando' => optional($resposta->created_at)->format('d/m H:i'),
                ];
            })
            ->values();
    }

    public function filtrarSubmissoes(Request $request)
    {
        $submissoesTable = (new SubmissaoAvaliacao)->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $avaliacaoId = $request->integer('avaliacao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $tipo = $this->tipoRequest($request);
        $isUniversal = $tipo === 'universal';

        return SubmissaoAvaliacao::query()
            ->when(
                $isUniversal,
                fn ($q) => $q->whereNull('atividade_id')->where('universal', true),
                fn ($q) => $q->whereNotNull('atividade_id')
            )
            ->when($tipo === 'transcricao', fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('transcricao', true)))
            ->when($tipo === 'momento', fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('transcricao', false)))
            ->when($templateId, fn ($q) => $q->whereHas('avaliacao', fn ($aq) => $aq->where('template_avaliacao_id', $templateId)))
            ->when($isUniversal && $avaliacaoId, fn ($q) => $q->where('avaliacao_id', $avaliacaoId))
            ->when(! $isUniversal && $atividadeId, fn ($q) => $q->where('atividade_id', $atividadeId))
            ->when(! $isUniversal && $eventoId, fn ($q) => $q->whereHas('atividade', fn ($aq) => $aq->where('evento_id', $eventoId)))
            ->when($de, fn ($q) => $q->whereDate("{$submissoesTable}.created_at", '>=', $de))
            ->when($ate, fn ($q) => $q->whereDate("{$submissoesTable}.created_at", '<=', $ate));
    }

    /**
     * Adapta uma Collection de modelos RespostaAvaliacao (eager-loaded) para o formato agregado
     * e chama montarPerguntas(). Usado por serviços que já carregaram os dados individualmente.
     */
    public function montarPerguntasFromRespostas(Collection $respostas): Collection
    {
        $questoes = $respostas
            ->pluck('avaliacaoQuestao')
            ->filter()
            ->keyBy('id');

        $agregados = $respostas
            ->groupBy(fn ($r) => ($r->avaliacao_questao_id ?? '').'|||'.($r->resposta ?? ''))
            ->map(fn ($group) => (object) [
                'avaliacao_questao_id' => $group->first()->avaliacao_questao_id,
                'resposta' => $group->first()->resposta,
                'count' => $group->count(),
                'ultima' => $group->max('created_at'),
            ])
            ->values();

        return $this->montarPerguntas($agregados, $questoes);
    }

    private function tipoRequest(Request $request): string
    {
        $tipo = $request->query('tipo');

        return in_array($tipo, ['universal', 'momento', 'transcricao'], true)
            ? $tipo
            : 'momento';
    }

    /**
     * Monta blocos de perguntas a partir de dados já agregados no banco (avaliacao_questao_id, resposta, count, ultima).
     * Cada $questao é um AvaliacaoQuestao carregado separadamente via whereIn.
     *
     * @param  Collection<int, object{avaliacao_questao_id: int, resposta: ?string, count: int, ultima: ?string}>  $agregados
     * @param  Collection<int, AvaliacaoQuestao>  $questoes  Keyed by id
     */
    public function montarPerguntas(Collection $agregados, Collection $questoes): Collection
    {
        $blocos = $agregados
            ->groupBy('avaliacao_questao_id')
            ->map(function ($items, $questaoId) use ($questoes) {
                $questao = $questoes->get($questaoId);
                $tipo = $questao?->tipo ?? 'texto';
                $totalRespostas = $items->sum('count');

                $bloco = [
                    'id' => $questao?->id ?? $questaoId,
                    'texto' => $questao?->texto ?? 'Questão',
                    'tipo' => $tipo,
                    'total' => $totalRespostas,
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
                    $sim = 0;
                    $nao = 0;
                    $invalidos = 0;

                    foreach ($items as $item) {
                        $bool = $this->respostaParaBool($item->resposta);
                        $cnt = (int) $item->count;
                        if ($bool === true) {
                            $sim += $cnt;
                        } elseif ($bool === false) {
                            $nao += $cnt;
                        } else {
                            $invalidos += $cnt;
                        }
                    }

                    $validTotal = $sim + $nao;
                    $bloco['labels'] = ['Sim', 'Não'];
                    $bloco['values'] = [$sim, $nao];

                    if ($invalidos > 0) {
                        $bloco['labels'][] = 'Indefinido';
                        $bloco['values'][] = $invalidos;
                    }

                    if ($totalRespostas === 0) {
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
                    [$labels, $values] = $this->montarDistribuicaoAgregada(
                        $items,
                        $questao?->escala?->valores ?? []
                    );
                    $bloco['labels'] = $labels;
                    $bloco['values'] = $values;

                    $media = $this->calcularMediaAgregada($items);
                    $bloco['media'] = $media;
                    $bloco['resumo'] = $media !== null ? 'Média '.number_format($media, 1, ',', '.') : null;

                    return $bloco;
                }

                if ($tipo === 'unica') {
                    [$labels, $values] = $this->montarDistribuicaoAgregada(
                        $items,
                        $questao?->opcoes_resposta ?? []
                    );
                    $bloco['labels'] = $labels;
                    $bloco['values'] = $values;

                    return $bloco;
                }

                if ($tipo === 'multipla') {
                    $opcoesConfiguradas = $questao?->opcoes_resposta ?? [];
                    $opcoes = collect($opcoesConfiguradas)
                        ->map(fn ($opcao) => is_string($opcao) ? trim($opcao) : '')
                        ->filter()
                        ->values()
                        ->all();

                    $contagem = array_fill_keys($opcoes, 0);

                    foreach ($items as $item) {
                        $cnt = (int) $item->count;
                        $valores = is_string($item->resposta)
                            ? json_decode($item->resposta, true)
                            : $item->resposta;

                        if (is_array($valores)) {
                            foreach ($valores as $valor) {
                                $v = trim((string) $valor);
                                if ($v === '') {
                                    continue;
                                }
                                if (! array_key_exists($v, $contagem)) {
                                    $contagem[$v] = 0;
                                }
                                $contagem[$v] += $cnt;
                            }
                        }
                    }

                    $bloco['labels'] = array_keys($contagem);
                    $bloco['values'] = array_values($contagem);

                    return $bloco;
                }

                if ($tipo === 'numero') {
                    $porValor = [];
                    $soma = 0.0;
                    $totalNums = 0;
                    $min = null;
                    $max = null;

                    foreach ($items as $item) {
                        $v = $this->respostaParaTexto($item->resposta);
                        if (! is_numeric($v)) {
                            continue;
                        }
                        $num = (float) $v;
                        $cnt = (int) $item->count;
                        $chave = (string) $num;
                        $porValor[$chave] = ($porValor[$chave] ?? 0) + $cnt;
                        $soma += $num * $cnt;
                        $totalNums += $cnt;
                        $min = $min === null ? $num : min($min, $num);
                        $max = $max === null ? $num : max($max, $num);
                    }

                    $bloco['labels'] = array_keys($porValor);
                    $bloco['values'] = array_values($porValor);
                    $media = $totalNums > 0 ? round($soma / $totalNums, 2) : null;
                    $bloco['media'] = $media;
                    $bloco['min'] = $min;
                    $bloco['max'] = $max;
                    $bloco['resumo'] = $media !== null
                        ? 'Média '.number_format($media, 2, ',', '.')
                        : null;

                    return $bloco;
                }

                // tipo texto: usa os valores agregados (cada texto único aparece uma vez)
                $respostasTexto = $items
                    ->sortByDesc('ultima')
                    ->map(fn ($item) => $this->respostaParaTexto($item->resposta))
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

    /**
     * Monta distribuição de opções a partir de dados pré-agregados (resposta, count).
     *
     * @param  Collection<int, object{resposta: ?string, count: int}>  $items
     */
    private function montarDistribuicaoAgregada(Collection $items, array $opcoesConfiguradas = []): array
    {
        $opcoes = collect($opcoesConfiguradas)
            ->map(fn ($opcao) => is_string($opcao) ? trim($opcao) : '')
            ->filter()
            ->values()
            ->all();

        if (empty($opcoes)) {
            foreach ($items as $item) {
                $valor = $this->respostaParaTexto($item->resposta);
                if ($valor !== '' && ! in_array($valor, $opcoes, true)) {
                    $opcoes[] = $valor;
                }
            }
        }

        $contagem = [];
        foreach ($opcoes as $opcao) {
            $contagem[$opcao] = 0;
        }

        foreach ($items as $item) {
            $valor = $this->respostaParaTexto($item->resposta);
            if ($valor === '') {
                continue;
            }
            $contagem[$valor] = ($contagem[$valor] ?? 0) + (int) $item->count;
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

    /**
     * Calcula média numérica a partir de dados pré-agregados (resposta, count).
     *
     * @param  Collection<int, object{resposta: ?string, count: int}>  $items
     */
    public function calcularMediaAgregada(Collection $items): ?float
    {
        $soma = 0.0;
        $total = 0;

        foreach ($items as $item) {
            $valor = $this->respostaParaTexto($item->resposta);
            if (! is_numeric($valor)) {
                continue;
            }
            $cnt = (int) $item->count;
            $soma += (float) $valor * $cnt;
            $total += $cnt;
        }

        if ($total === 0) {
            return null;
        }

        return round($soma / $total, 1);
    }
}
