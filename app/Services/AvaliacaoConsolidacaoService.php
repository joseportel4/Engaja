<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\RespostaAvaliacao;
use Illuminate\Support\Collection;

class AvaliacaoConsolidacaoService
{
    public function __construct(private AvaliacaoRespostasDashboardService $dashboard)
    {
    }

    public function build(Evento $evento, string $agrupamento = 'geral'): array
    {
        $agrupamento = $agrupamento === 'regiao' ? 'regiao' : 'geral';

        $respostas = RespostaAvaliacao::query()
            ->select([
                'id',
                'avaliacao_id',
                'avaliacao_questao_id',
                'submissao_avaliacao_id',
                'resposta',
                'created_at',
            ])
            ->with([
                'avaliacaoQuestao.escala',
                'avaliacaoQuestao.indicador.dimensao',
                'avaliacao.templateAvaliacao',
                'avaliacao.atividade.municipio.estado.regiao',
                'avaliacao.atividade.municipios.estado.regiao',
            ])
            ->whereHas('avaliacao.atividade', fn ($q) => $q->where('evento_id', $evento->id))
            ->get();

        $templateNames = [];
        $grouped = [];

        foreach ($respostas as $resposta) {
            $avaliacao = $resposta->avaliacao;
            if (! $avaliacao) {
                continue;
            }

            $template = $avaliacao->templateAvaliacao;
            $templateId = $template?->id ?? 0;
            $templateNames[$templateId] = $template?->nome ?? 'Modelo sem nome';

            $groupKeys = $this->resolveGroupKeys($resposta, $agrupamento);
            foreach ($groupKeys as $groupKey) {
                $grouped[$groupKey][$templateId][] = $resposta;
            }
        }

        $groups = collect($grouped)->map(function (array $templates, string $groupName) use ($templateNames) {
            $templatesPayload = collect($templates)->map(function (array $items, int $templateId) use ($templateNames) {
                $collection = collect($items);
                $perguntas = $this->dashboard->montarPerguntas($collection)
                    ->map(fn (array $pergunta) => $this->enrichPerguntaResumo($pergunta))
                    ->values();

                return [
                    'template_id' => $templateId ?: null,
                    'template_nome' => $templateNames[$templateId] ?? 'Modelo sem nome',
                    'submissoes' => $collection->pluck('submissao_avaliacao_id')->filter()->unique()->count(),
                    'respostas' => $collection->count(),
                    'perguntas' => $perguntas->all(),
                ];
            })->sortBy('template_nome')->values()->all();

            return [
                'nome' => $groupName,
                'templates' => $templatesPayload,
            ];
        })->values();

        return $this->sortGroups($groups, $agrupamento)->all();
    }

    private function resolveGroupKeys(RespostaAvaliacao $resposta, string $agrupamento): array
    {
        if ($agrupamento !== 'regiao') {
            return ['Todos os municípios'];
        }

        $atividade = $resposta->avaliacao?->atividade;
        if (! $atividade) {
            return ['Sem região'];
        }

        $municipios = $atividade->municipios->isNotEmpty()
            ? $atividade->municipios
            : collect([$atividade->municipio])->filter();

        $regioes = $municipios
            ->map(fn ($municipio) => $municipio?->estado?->regiao?->nome)
            ->filter()
            ->unique()
            ->values();

        return $regioes->isNotEmpty() ? $regioes->all() : ['Sem região'];
    }

    private function enrichPerguntaResumo(array $pergunta): array
    {
        if (! empty($pergunta['resumo'])) {
            return $pergunta;
        }

        $tipo = $pergunta['tipo'] ?? 'texto';
        $total = (int) ($pergunta['total'] ?? 0);
        if ($total === 0) {
            $pergunta['resumo'] = 'Sem respostas';
            return $pergunta;
        }

        if (in_array($tipo, ['unica', 'multipla'], true)) {
            $labels = $pergunta['labels'] ?? [];
            $values = $pergunta['values'] ?? [];
            if (empty($labels) || empty($values)) {
                $pergunta['resumo'] = 'Respostas: '.$total;
                return $pergunta;
            }

            $maxValue = max($values);
            $maxIndex = array_search($maxValue, $values, true);
            $label = $labels[$maxIndex] ?? 'Opção';
            $totalSelecoes = array_sum($values);
            $percent = $totalSelecoes > 0 ? round(($maxValue / $totalSelecoes) * 100) : 0;

            $textoQuantidade = $maxValue === 1 ? '1 resposta' : $maxValue.' respostas';
            $pergunta['resumo'] = 'Mais citado: '.$label.' ('.$textoQuantidade.', '.$percent.'%)';
            return $pergunta;
        }

        if ($tipo === 'texto') {
            $totalTexto = (int) ($pergunta['respostas_total'] ?? $total);
            $pergunta['resumo'] = 'Respostas abertas: '.$totalTexto;
            return $pergunta;
        }

        $pergunta['resumo'] = 'Respostas: '.$total;
        return $pergunta;
    }

    private function sortGroups(Collection $groups, string $agrupamento): Collection
    {
        if ($agrupamento !== 'regiao') {
            return $groups;
        }

        return $groups->sortBy(function (array $group) {
            return $group['nome'] === 'Sem região' ? 'zzzzzz' : mb_strtolower((string) $group['nome']);
        })->values();
    }
}
