<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use Illuminate\Http\Request;

class EscalaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descricao' => 'required|string|max:255',
            'opcao1'    => 'nullable|string|max:255',
            'opcao2'    => 'nullable|string|max:255',
            'opcao3'    => 'nullable|string|max:255',
            'opcao4'    => 'nullable|string|max:255',
            'opcao5'    => 'nullable|string|max:255',
        ]);

        Escala::create($request->only([
            'descricao',
            'opcao1', 'opcao2', 'opcao3', 'opcao4', 'opcao5',
        ]));

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Escala $escala)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Escala $escala)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Escala $escala)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Escala $escala)
    {
        //
    }
}
