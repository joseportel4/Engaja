<?php

namespace App\Http\Controllers;

use App\Models\Eixo;
use App\Models\Escala;
use App\Models\Indicador;
use App\Models\Questao;
use Illuminate\Http\Request;

class QuestaoController extends Controller
{
    public function index()
    {
        $questaos = Questao::with(['indicador.dimensao', 'escala'])
            ->orderBy('texto')
            ->paginate(15);

        return view('questaos.index', compact('questaos'));
    }

    public function create()
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? "{$indicador->dimensao->descricao} - {$indicador->descricao}"
                    : $indicador->descricao,
            ]);

        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');

        return view('questaos.create', compact('indicadores', 'escalas'));
    }

    public function store(Request $request)
    {
        $dados = $this->validateQuestao($request);

        Questao::create($dados);

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão criada com sucesso!');
    }

    public function show(Questao $questao)
    {
        $questao->load(['indicador.dimensao', 'escala', 'templates']);

        return view('questaos.show', compact('questao'));
    }

    public function edit(Questao $questao)
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? "{$indicador->dimensao->descricao} - {$indicador->descricao}"
                    : $indicador->descricao,
            ]);

        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');

        return view('questaos.edit', compact('questao', 'indicadores', 'escalas'));
    }

    public function update(Request $request, Questao $questao)
    {
        $dados = $this->validateQuestao($request);

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
            'indicador_id' => ['required', Rule::exists('indicadors', 'id')],
            'escala_id'    => ['nullable', Rule::exists('escalas', 'id')],
            'texto'        => ['required', 'string', 'max:1000'],
            'tipo'         => ['required', 'string', Rule::in(['texto', 'escala', 'numero', 'boolean'])],
            'fixa'         => ['nullable', 'boolean'],
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
}
