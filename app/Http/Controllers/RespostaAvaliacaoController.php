<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\Questao;
use App\Models\RespostaAvaliacao;
use Illuminate\Http\Request;

class RespostaAvaliacaoController extends Controller
{
    public function index()
    {
        $respostas = RespostaAvaliacao::with(['avaliacao', 'questao'])
            ->paginate(15);
        return view('respostas.index', compact('respostas'));
    }


    public function create()
    {
        $avaliacoes = Avaliacao::pluck('id', 'id');
        $questoes = Questao::pluck('texto', 'id');
        return view('respostas.create', compact('avaliacoes', 'questoes'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'avaliacao_id' => 'required|exists:avaliacaos,id',
            'questao_id' => 'required|exists:questaos,id',
            'resposta' => 'nullable|string',
        ]);


        RespostaAvaliacao::create($request->only([
            'avaliacao_id', 'questao_id', 'resposta'
        ]));


        return redirect()->route('respostas.index')
            ->with('success', 'Resposta registrada com sucesso!');
    }


    public function show(RespostaAvaliacao $resposta)
    {
        return view('respostas.show', compact('resposta'));
    }


    public function edit(RespostaAvaliacao $resposta)
    {
        $avaliacoes = Avaliacao::pluck('id', 'id');
        $questoes = Questao::pluck('texto', 'id');
        return view('respostas.edit', compact('resposta', 'avaliacoes', 'questoes'));
    }


    public function update(Request $request, RespostaAvaliacao $resposta)
    {
        $request->validate([
            'avaliacao_id' => 'required|exists:avaliacaos,id',
            'questao_id' => 'required|exists:questaos,id',
            'resposta' => 'nullable|string',
        ]);


        $resposta->update($request->only([
            'avaliacao_id', 'questao_id', 'resposta'
        ]));


        return redirect()->route('respostas.index')
            ->with('success', 'Resposta atualizada com sucesso!');
    }


    public function destroy(RespostaAvaliacao $resposta)
    {
        $resposta->delete();
        return redirect()->route('respostas.index')
            ->with('success', 'Resposta removida com sucesso!');
    }
}
