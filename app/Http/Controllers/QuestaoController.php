<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use App\Models\Indicador;
use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestaoController extends Controller
{
    public function index()
    {
        $questaos = Questao::with(['indicador.dimensao', 'escala', 'template'])
            ->orderBy('texto')
            ->paginate(15);

        return view('questaos.index', compact('questaos'));
    }

    public function create()
    {
        [$indicadores, $escalas, $templates] = $this->formSelections();

        return view('questaos.create', compact('indicadores', 'escalas', 'templates'));
    }

    public function store(Request $request)
    {
        $dados = $this->validateQuestao($request);

        $dados['ordem'] = $dados['ordem'] ?? $this->proximaOrdem($dados['template_avaliacao_id']);

        Questao::create($dados);

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão criada com sucesso!');
    }

    public function show(Questao $questao)
    {
        $questao->load(['indicador.dimensao', 'escala', 'template']);

        return view('questaos.show', compact('questao'));
    }

    public function edit(Questao $questao)
    {
        [$indicadores, $escalas, $templates] = $this->formSelections();

        return view('questaos.edit', compact('questao', 'indicadores', 'escalas', 'templates'));
    }

    public function update(Request $request, Questao $questao)
    {
        $dados = $this->validateQuestao($request);

        $dados['ordem'] = $dados['ordem'] ?? $questao->ordem ?? $this->proximaOrdem($dados['template_avaliacao_id']);

        $questao->update($dados);

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão atualizada com sucesso!');
    }

    public function destroy(Questao $questao)
    {
        $questao->delete();

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão removida com sucesso!');
    }

    private function validateQuestao(Request $request): array
    {
        $dados = $request->validate([
            'template_avaliacao_id' => ['required', Rule::exists('template_avaliacaos', 'id')],
            'indicador_id'          => ['required', Rule::exists('indicadors', 'id')],
            'escala_id'             => ['nullable', Rule::exists('escalas', 'id')],
            'texto'                 => ['required', 'string', 'max:1000'],
            'tipo'                  => ['required', 'string', Rule::in(['texto', 'escala', 'numero', 'boolean'])],
            'ordem'                 => ['nullable', 'integer', 'min:1', 'max:999'],
            'fixa'                  => ['nullable', 'boolean'],
        ]);

        $dados['fixa'] = $request->boolean('fixa');

        if ($dados['tipo'] === 'escala' && empty($dados['escala_id'])) {
            $request->validate([
                'escala_id' => ['required', Rule::exists('escalas', 'id')],
            ]);
        }

        if ($dados['tipo'] !== 'escala') {
            $dados['escala_id'] = null;
        }

        return $dados;
    }

    private function proximaOrdem(int $templateId): int
    {
        $maiorOrdem = Questao::where('template_avaliacao_id', $templateId)->max('ordem');

        return $maiorOrdem ? $maiorOrdem + 1 : 1;
    }

    private function formSelections(): array
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
        $templates = TemplateAvaliacao::orderBy('nome')->pluck('nome', 'id');

        return [$indicadores, $escalas, $templates];
    }
}
