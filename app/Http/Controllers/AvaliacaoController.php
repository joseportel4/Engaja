<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\Inscricao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;

class AvaliacaoController extends Controller
{
    public function index()
    {
        $avaliacoes = Avaliacao::with(['inscricao', 'atividade', 'templateAvaliacao'])
            ->paginate(15);
        return view('avaliacoes.index', compact('avaliacoes'));
    }


    public function create()
    {
        $inscricoes = Inscricao::pluck('id', 'id'); // ajuste display se tiver relacionamento
        $atividades = Atividade::pluck('id', 'id');
        $templates = TemplateAvaliacao::pluck('nome', 'id');
        return view('avaliacoes.create', compact('inscricoes', 'atividades', 'templates'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'inscricao_id' => 'required|exists:inscricaos,id',
            'atividade_id' => 'required|exists:atividades,id',
            'template_avaliacao_id' => 'required|exists:template_avaliacaos,id',
        ]);


        Avaliacao::create($request->only([
            'inscricao_id', 'atividade_id', 'template_avaliacao_id'
        ]));


        return redirect()->route('avaliacoes.index')
            ->with('success', 'Avaliação criada com sucesso!');
    }


    public function show(Avaliacao $avaliacao)
    {
        $avaliacao->load('respostas', 'templateAvaliacao');
        return view('avaliacoes.show', compact('avaliacao'));
    }


    public function edit(Avaliacao $avaliacao)
    {
        $inscricoes = Inscricao::pluck('id', 'id');
        $atividades = Atividade::pluck('id', 'id');
        $templates = TemplateAvaliacao::pluck('nome', 'id');
        return view('avaliacoes.edit', compact('avaliacao', 'inscricoes', 'atividades', 'templates'));
    }


    public function update(Request $request, Avaliacao $avaliacao)
    {
        $request->validate([
            'inscricao_id' => 'required|exists:inscricaos,id',
            'atividade_id' => 'required|exists:atividades,id',
            'template_avaliacao_id' => 'required|exists:template_avaliacaos,id',
        ]);


        $avaliacao->update($request->only([
            'inscricao_id', 'atividade_id', 'template_avaliacao_id'
        ]));


        return redirect()->route('avaliacoes.index')
            ->with('success', 'Avaliação atualizada com sucesso!');
    }


    public function destroy(Avaliacao $avaliacao)
    {
        $avaliacao->delete();
        return redirect()->route('avaliacoes.index')
            ->with('success', 'Avaliação removida com sucesso!');
    }
}
