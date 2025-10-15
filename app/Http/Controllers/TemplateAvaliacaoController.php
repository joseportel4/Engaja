<?php

namespace App\Http\Controllers;

use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;

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
        $questaos = $this->listaQuestoes();

        return view('templates-avaliacao.create', compact('questaos'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'nome'        => ['required', 'string', 'max:255'],
            'descricao'   => ['nullable', 'string'],
            'questoes'    => ['nullable', 'array'],
            'questoes.*'  => ['integer', 'exists:questaos,id'],
            'ordens'      => ['nullable', 'array'],
            'ordens.*'    => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        DB::transaction(function () use ($dados, $request) {
            $template = TemplateAvaliacao::create([
                'nome'      => $dados['nome'],
                'descricao' => $dados['descricao'] ?? null,
            ]);

            $this->sincronizaQuestoes($template, $request);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação criado com sucesso!');
    }

    public function show(TemplateAvaliacao $templates_avaliacao)
    {
        $templates_avaliacao->load(['questoes.indicador.dimensao', 'questoes.escala']);

        return view('templates-avaliacao.show', [
            'template' => $templates_avaliacao,
        ]);
    }

    public function edit(TemplateAvaliacao $templates_avaliacao)
    {
        $templates_avaliacao->load('questoes');
        $questaos = $this->listaQuestoes();

        return view('templates-avaliacao.edit', [
            'template' => $templates_avaliacao,
            'questaos' => $questaos,
        ]);
    }

    public function update(Request $request, TemplateAvaliacao $templates_avaliacao)
    {
        $dados = $request->validate([
            'nome'        => ['required', 'string', 'max:255'],
            'descricao'   => ['nullable', 'string'],
            'questoes'    => ['nullable', 'array'],
            'questoes.*'  => ['integer', 'exists:questaos,id'],
            'ordens'      => ['nullable', 'array'],
            'ordens.*'    => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        DB::transaction(function () use ($templates_avaliacao, $dados, $request) {
            $templates_avaliacao->update([
                'nome'      => $dados['nome'],
                'descricao' => $dados['descricao'] ?? null,
            ]);

            $this->sincronizaQuestoes($templates_avaliacao, $request);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação atualizado com sucesso!');
    }

    public function destroy(TemplateAvaliacao $templates_avaliacao)
    {
        $templates_avaliacao->delete();

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação removido com sucesso!');
    }

    private function sincronizaQuestoes(TemplateAvaliacao $template, Request $request): void
    {
        $questoesSelecionadas = $request->input('questoes', []);
        $ordensInformadas = $request->input('ordens', []);

        $pivotData = [];
        foreach ($questoesSelecionadas as $index => $questaoId) {
            $ordem = $ordensInformadas[$questaoId] ?? ($index + 1);
            $pivotData[$questaoId] = ['ordem' => $ordem];
        }

        $template->questoes()->sync($pivotData);
    }

    private function listaQuestoes()
    {
        return Questao::with(['indicador.dimensao', 'escala'])
            ->orderBy('texto')
            ->get();
    }
}
