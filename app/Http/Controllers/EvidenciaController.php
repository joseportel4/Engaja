<?php

namespace App\Http\Controllers;

use App\Models\Evidencia;
use App\Models\Indicador;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvidenciaController extends Controller
{
    public function index()
    {
        $evidencias = Evidencia::with('indicador.dimensao')
            ->orderBy('descricao')
            ->paginate(15);

        return view('evidencias.index', compact('evidencias'));
    }

    public function create()
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? $indicador->dimensao->descricao . ' - ' . $indicador->descricao
                    : $indicador->descricao,
            ]);

        return view('evidencias.create', compact('indicadores'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'indicador_id' => ['required', Rule::exists('indicadors', 'id')],
            'descricao'    => ['required', 'string', 'max:255'],
        ]);

        Evidencia::create($dados);

        return redirect()->route('evidencias.index')->with('success', 'Evidência criada com sucesso!');
    }

    public function show(Evidencia $evidencia)
    {
        $evidencia->load('indicador.dimensao');

        return view('evidencias.show', compact('evidencia'));
    }

    public function edit(Evidencia $evidencia)
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? $indicador->dimensao->descricao . ' - ' . $indicador->descricao
                    : $indicador->descricao,
            ]);

        return view('evidencias.edit', compact('evidencia', 'indicadores'));
    }

    public function update(Request $request, Evidencia $evidencia)
    {
        $dados = $request->validate([
            'indicador_id' => ['required', Rule::exists('indicadors', 'id')],
            'descricao'    => ['required', 'string', 'max:255'],
        ]);

        $evidencia->update($dados);

        return redirect()->route('evidencias.index')->with('success', 'Evidência atualizada com sucesso!');
    }

    public function destroy(Evidencia $evidencia)
    {
        $evidencia->delete();

        return redirect()->route('evidencias.index')->with('success', 'Evidência removida com sucesso!');
    }
}

