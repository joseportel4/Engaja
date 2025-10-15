<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use Illuminate\Http\Request;

class EscalaController extends Controller
{
    public function index()
    {
        $escalas = Escala::orderBy('descricao')->paginate(15);
        return view('escalas.index', compact('escalas'));
    }

    public function create()
    {
        return view('escalas.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'descricao' => 'required|string|max:255',
            'opcao1'    => 'nullable|string|max:255',
            'opcao2'    => 'nullable|string|max:255',
            'opcao3'    => 'nullable|string|max:255',
            'opcao4'    => 'nullable|string|max:255',
            'opcao5'    => 'nullable|string|max:255',
        ]);

        Escala::create($dados);

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala criada com sucesso!');
    }

    public function show(Escala $escala)
    {
        return view('escalas.show', compact('escala'));
    }

    public function edit(Escala $escala)
    {
        return view('escalas.edit', compact('escala'));
    }

    public function update(Request $request, Escala $escala)
    {
        $dados = $request->validate([
            'descricao' => 'required|string|max:255',
            'opcao1'    => 'nullable|string|max:255',
            'opcao2'    => 'nullable|string|max:255',
            'opcao3'    => 'nullable|string|max:255',
            'opcao4'    => 'nullable|string|max:255',
            'opcao5'    => 'nullable|string|max:255',
        ]);

        $escala->update($dados);

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala atualizada com sucesso!');
    }

    public function destroy(Escala $escala)
    {
        $escala->delete();

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala removida com sucesso!');
    }
}
