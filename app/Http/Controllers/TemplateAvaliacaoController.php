<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use App\Models\Indicador;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TemplateAvaliacaoController extends Controller
{
    public function index()
    {
        $templates = TemplateAvaliacao::withCount('questoes')
            ->orderBy('nome')
            ->paginate(15);

        return view('templates-avaliacao.index', compact('templates'));
    }

    public function create()
    {
        return view('templates-avaliacao.create', $this->formDependencies());
    }

    public function store(Request $request)
    {
        $dadosTemplate = $this->validateTemplate($request);
        [$questoes] = $this->processaQuestoes($request, false);

        DB::transaction(function () use ($dadosTemplate, $questoes) {
            $template = TemplateAvaliacao::create($dadosTemplate);
            $this->persistQuestoes($template, $questoes);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação criado com sucesso!');
    }

    public function show(TemplateAvaliacao $template)
    {
        $template->load(['questoes.indicador.dimensao', 'questoes.escala']);

        return view('templates-avaliacao.show', compact('template'));
    }

    public function edit(TemplateAvaliacao $template)
    {
        $template->load(['questoes.indicador.dimensao', 'questoes.escala']);

        return view('templates-avaliacao.edit',
            array_merge($this->formDependencies(), compact('template'))
        );
    }

    public function update(Request $request, TemplateAvaliacao $template)
    {
        $dadosTemplate = $this->validateTemplate($request);
        [$questoes, $removidas] = $this->processaQuestoes($request, true);

        DB::transaction(function () use ($template, $dadosTemplate, $questoes, $removidas) {
            $template->update($dadosTemplate);
            $this->persistQuestoes($template, $questoes, $removidas);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação atualizado com sucesso!');
    }

    public function destroy(TemplateAvaliacao $template)
    {
        $template->delete();

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação removido com sucesso!');
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ]);
    }

    private function formDependencies(): array
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? $indicador->dimensao->descricao . ' - ' . $indicador->descricao
                    : $indicador->descricao,
            ]);

        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');

        $tiposQuestao = [
            'texto'  => 'Texto aberto',
            'escala' => 'Escala',
            'numero' => 'Numérica',
            'boolean'=> 'Sim/Não',
        ];

        return compact('indicadores', 'escalas', 'tiposQuestao');
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array}
     */
    private function processaQuestoes(Request $request, bool $permitirIds): array
    {
        $questoesInput = collect($request->input('questoes', []));
        $questoesAtivas = $questoesInput
            ->filter(fn ($questao) => empty($questao['_delete']))
            ->values();

        if ($questoesAtivas->isEmpty()) {
            throw ValidationException::withMessages([
                'questoes' => 'Informe pelo menos uma questão para o template.',
            ]);
        }

        $questoesValidadas = $questoesAtivas->map(function ($questao, int $index) use ($permitirIds) {
            // indicador_id is required only for fixed questions (fixa=true).
            $validator = Validator::make(
                $questao,
                [
                    'id'           => $permitirIds
                        ? ['nullable', 'integer', Rule::exists('questaos', 'id')->whereNull('deleted_at')]
                        : ['prohibited'],
                    'indicador_id' => ['nullable', 'integer', Rule::exists('indicadors', 'id')],
                    'escala_id'    => ['nullable', 'integer', Rule::exists('escalas', 'id')],
                    'texto'        => ['required', 'string', 'max:1000'],
                    'tipo'         => ['required', 'string', Rule::in(['texto', 'escala', 'numero', 'boolean'])],
                    'ordem'        => ['nullable', 'integer', 'min:1', 'max:999'],
                    'fixa'         => ['nullable', 'boolean'],
                ],
                [],
                [
                    'id'           => "questoes.$index.id",
                    'indicador_id' => "questoes.$index.indicador_id",
                    'escala_id'    => "questoes.$index.escala_id",
                    'texto'        => "questoes.$index.texto",
                    'tipo'         => "questoes.$index.tipo",
                    'ordem'        => "questoes.$index.ordem",
                    'fixa'         => "questoes.$index.fixa",
                ]
            );

            $validator->after(function ($validator) use ($questao) {
                // Escala must be selected for 'escala' type
                if (($questao['tipo'] ?? null) === 'escala' && empty($questao['escala_id'])) {
                    $validator->errors()->add('escala_id', 'Selecione uma escala para questões do tipo "Escala".');
                }

                // If question is fixed, indicador_id is required
                $isFixa = ! empty($questao['fixa']);
                if ($isFixa && empty($questao['indicador_id'])) {
                    $validator->errors()->add('indicador_id', 'Selecione um indicador para questões fixas.');
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $dados = $validator->validated();
            $dados['fixa'] = ! empty($questao['fixa']);

            if (($dados['tipo'] ?? null) !== 'escala') {
                $dados['escala_id'] = null;
            }

            $dados['ordem'] = $dados['ordem'] ?? ($index + 1);

            return $dados;
        });

        $questoesOrdenadas = $questoesValidadas
            ->sortBy(fn ($questao, $idx) => $questao['ordem'] ?? ($idx + 1))
            ->values();

        $questoesNormalizadas = $questoesOrdenadas->map(function ($questao, int $idx) {
            $questao['ordem'] = $questao['ordem'] ?? ($idx + 1);

            return $questao;
        });

        $idsRemovidos = $questoesInput
            ->filter(fn ($questao) => ! empty($questao['_delete']) && ! empty($questao['id']))
            ->pluck('id')
            ->all();

        return [$questoesNormalizadas, $idsRemovidos];
    }

    private function persistQuestoes(TemplateAvaliacao $template, Collection $questoes, array $removidas = []): void
    {
        if (! empty($removidas)) {
            $template->questoes()->whereIn('id', $removidas)->delete();
        }

        $idsMantidos = [];

        foreach ($questoes as $questao) {
            $dados = $questao;
            $questaoId = $dados['id'] ?? null;
            unset($dados['id']);

            if ($questaoId) {
                $modelo = $template->questoes()->whereKey($questaoId)->firstOrFail();
                $modelo->update($dados);
                $idsMantidos[] = $modelo->id;

                continue;
            }

            $dados['template_avaliacao_id'] = $template->id;
            $modelo = $template->questoes()->create($dados);
            $idsMantidos[] = $modelo->id;
        }

        if (! empty($idsMantidos)) {
            $template->questoes()->whereNotIn('id', $idsMantidos)->delete();
        } else {
            $template->questoes()->delete();
        }
    }
}
