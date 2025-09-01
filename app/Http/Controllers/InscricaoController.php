<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Imports\ParticipantesImport;
use Maatwebsite\Excel\Facades\Excel;

class InscricaoController extends Controller
{
    /**
     * Lista (se necessário)
     */
    public function index()
    {
        //
    }

    /**
     * Formulário de upload do .xlsx
     */
    public function import(Evento $evento)
    {
        return view('inscricoes.import', compact('evento'));
    }

    /**
     * Recebe o arquivo e executa o import.
     */
    public function cadastro(Request $request, Evento $evento)
    {
        // validação básica do upload
        $request->validate([
            'your_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            // Passamos o ID do evento para o import para vincular cada participante
            Excel::import(new ParticipantesImport($evento->id), $request->file('your_file'));

            return redirect()
                ->route('eventos.show', $evento)
                ->with('success', 'Participantes importados com sucesso!');
        } catch (\Throwable $e) {
            // Você pode logar o erro se quiser: \Log::error($e);
            return back()
                ->withErrors(['your_file' => 'Falha ao importar o arquivo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
