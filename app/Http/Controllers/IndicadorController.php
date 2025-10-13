<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use App\Models\Dimensao;
use Illuminate\Http\Request;

class IndicadorController extends Controller
{
    public function index()
    {
        $indicadors = Indicador::with('dimensao')->orderBy('descricao')->paginate(15);
        return view('indicadors.index', compact('indicadors'));
    }

    public function create()
    {
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');
        return view('indicadors.create', compact('dimensoes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dimensao_id' => 'required|exists:dimensaos,id',
            'descricao'   => 'required|string|max:255',
        ]);
        Indicador::create($request->only('dimensao_id', 'descricao'));
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador criado com sucesso!');
    }

    public function show(Indicador $indicador)
    {
        return view('indicadors.show', compact('indicador'));
    }

    public function edit(Indicador $indicador)
    {
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');
        return view('indicadors.edit', compact('indicador', 'dimensoes'));
    }

    public function update(Request $request, Indicador $indicador)
    {
        $request->validate([
            'dimensao_id' => 'required|exists:dimensaos,id',
            'descricao'   => 'required|string|max:255',
        ]);
        $indicador->update($request->only('dimensao_id', 'descricao'));
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador atualizado com sucesso!');
    }

    public function destroy(Indicador $indicador)
    {
        $indicador->delete();
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador removido com sucesso!');
    }
}
