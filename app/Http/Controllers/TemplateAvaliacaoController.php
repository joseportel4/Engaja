<?php

namespace App\Http\Controllers;

use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;

class TemplateAvaliacaoController extends Controller
{
    public function index()
    {
        $questaos = Questao::orderBy('texto')->pluck('texto', 'id');
        return view('templates.create', compact('questaos'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'questaos' => 'array',
            'questaos.*' => 'exists:questaos,id',
            'ordem' => 'array',
        ]);


        $template = TemplateAvaliacao::create($request->only(['nome', 'descricao']));
        // vincula questões com a ordem opcional
        if ($request->filled('questaos')) {
            $syncData = [];
            foreach ($request->input('questaos') as $idx => $qId) {
                $syncData[$qId] = ['ordem' => $request->input('ordem.' . $idx, $idx)];
            }
            $template->questoes()->sync($syncData);
        }


        return redirect()->route('templates.index')
            ->with('success', 'Template criado com sucesso!');
    }


    public function show(TemplateAvaliacao $template)
    {
        $template->load('questoes');
        return view('templates.show', compact('template'));
    }


    public function edit(TemplateAvaliacao $template)
    {
        $questaos = Questao::orderBy('texto')->pluck('texto', 'id');
        $template->load('questoes');
        return view('templates.edit', compact('template', 'questaos'));
    }


    public function update(Request $request, TemplateAvaliacao $template)
    {
        $request->validate([
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'questaos' => 'array',
            'questaos.*' => 'exists:questaos,id',
            'ordem' => 'array',
        ]);


        $template->update($request->only(['nome', 'descricao']));
        $syncData = [];
        foreach ($request->input('questaos', []) as $idx => $qId) {
            $syncData[$qId] = ['ordem' => $request->input('ordem.' . $idx, $idx)];
        }
        $template->questoes()->sync($syncData);


        return redirect()->route('templates.index')
            ->with('success', 'Template atualizado com sucesso!');
    }


    public function destroy(TemplateAvaliacao $template)
    {
        $template->delete();
        return redirect()->route('templates.index')
            ->with('success', 'Template removido com sucesso!');
    }
}
