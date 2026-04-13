<?php

namespace App\Http\Controllers;

use App\Imports\AutorizacaoImagemPreviewImport;
use App\Models\Participante;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Maatwebsite\Excel\Facades\Excel;

class AutorizacaoImagemImportController extends Controller
{
    public function import()
    {
        return view('usuarios.autorizacoes.import');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $import = new AutorizacaoImagemPreviewImport();
        Excel::import($import, $request->file('arquivo'));

        $cpfsPlanilha = collect($import->rows)->map(function ($row) {
            return preg_replace('/\D+/', '', $row['cpf'] ?? '');
        })->filter()->toArray();

        $participantes = Participante::with('user')
            ->whereNotNull('cpf')
            ->get()
            ->keyBy(function ($item) {
                return preg_replace('/\D+/', '', $item->cpf);
            });

        $previewData = [];

        foreach ($import->rows as $row) {
            $cpfLimpo = preg_replace('/\D+/', '', $row['cpf'] ?? '');
            if (empty($cpfLimpo)) continue;

            $participante = $participantes->get($cpfLimpo);

            $previewData[] = [
                'nome_planilha'   => $row['nome'] ?? 'Sem Nome',
                'cpf_planilha'    => $row['cpf'],
                'participante_id' => $participante ? $participante->id : null,
                'nome_sistema'    => $participante->user->name ?? '-',
                'status'          => $participante ? 'Pronto para atualizar' : 'Participante não encontrado',
                'pode_atualizar'  => $participante ? true : false,
            ];
        }

        $sessionKey = 'import_autorizacoes_' . Str::uuid();
        session([$sessionKey => $previewData]);

        // Após processar, redireciona para a tela de preview passando a chave da sessão
        return redirect()->route('usuarios.autorizacoes.preview', ['session_key' => $sessionKey]);
    }

    public function preview(Request $request)
    {
        $sessionKey = $request->query('session_key');
        $previewData = session($sessionKey);

        if (!is_array($previewData)) {
            return redirect()->route('usuarios.autorizacoes.import')
                ->with('error', 'Sessão expirada. Por favor, envie o arquivo novamente.');
        }

        //paginação
        $perPage = 25; //exibe 25 registros por página
        $page = (int) max(1, $request->query('page', 1));
        $rows = collect($previewData);
        $slice = $rows->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => route('usuarios.autorizacoes.preview'),
                'query' => ['session_key' => $sessionKey],
            ]
        );

        //calcula os totais de forma global para a view
        $prontos = $rows->where('pode_atualizar', true)->count();
        $erros = $rows->where('pode_atualizar', false)->count();

        return view('usuarios.autorizacoes.preview', compact('paginator', 'sessionKey', 'prontos', 'erros'));
    }

    public function confirmar(Request $request)
    {
        $sessionKey = $request->input('session_key');
        $previewData = session($sessionKey);

        if (!$previewData) {
            return redirect()->route('usuarios.autorizacoes.import')
                ->with('error', 'Sessão expirada. Por favor, envie o arquivo novamente.');
        }

        $idsParaAtualizar = collect($previewData)
            ->where('pode_atualizar', true)
            ->pluck('participante_id')
            ->toArray();

        if (!empty($idsParaAtualizar)) {
            Participante::whereIn('id', $idsParaAtualizar)->update(['autorizacao_imagem' => true]);
        }

        session()->forget($sessionKey);

        return redirect()->route('usuarios.index')
            ->with('success', count($idsParaAtualizar) . ' autorizações de imagem foram registradas com sucesso!');
    }
}
