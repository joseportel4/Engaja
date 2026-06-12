<?php

namespace App\Http\Controllers;

use App\Exports\UsuariosSemVinculoExport;
use App\Services\UsuariosSemVinculoService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UsuariosSemVinculoController extends Controller
{
    public function index(Request $request, UsuariosSemVinculoService $service): View
    {
        $usuarios = $service
            ->query($request->user())
            ->paginate(50);

        return view('usuarios.sem-vinculo.index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function exportar(Request $request)
    {
        return Excel::download(
            new UsuariosSemVinculoExport($request->user()),
            'usuarios-sem-vinculo-' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
