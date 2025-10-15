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
        $questaos = Questao::with(['indicador', 'escala'])
            ->orderBy('texto')
            ->paginate(15);
        return view('questaos.index', compact('questaos'));
    }


    public function create()
    {
        $indicadores = Indicador::orderBy('descricao')->pluck('descricao', 'id');
        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');
        return view('questaos.create', compact('indicadores', 'escalas'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'escala_id' => 'nullable|exists:escalas,id',
            'texto' => 'required|string',
            'tipo' => 'required|string',
            'fixa' => 'boolean',
        ]);


        Questao::create($request->only([
            'indicador_id', 'escala_id', 'texto', 'tipo', 'fixa'
        ]));


        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão criada com sucesso!');
    }


    public function show(Questao $questao)
    {
        return view('questaos.show', compact('questao'));
    }


    public function edit(Questao $questao)
    {
        $indicadores = Indicador::orderBy('descricao')->pluck('descricao', 'id');
        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');
        return view('questaos.edit', compact('questao', 'indicadores', 'escalas'));
    }


    public function update(Request $request, Questao $questao)
    {
        $request->validate([
            'indicador_id' => 'required|exists:indicadors,id',
            'escala_id' => 'nullable|exists:escalas,id',
            'texto' => 'required|string',
            'tipo' => 'required|string',
            'fixa' => 'boolean',
        ]);


        $questao->update($request->only([
            'indicador_id', 'escala_id', 'texto', 'tipo', 'fixa'
        ]));


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
}
