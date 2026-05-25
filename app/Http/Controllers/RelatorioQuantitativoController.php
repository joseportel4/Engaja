<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioMomentoExport;
use App\Exports\RelatorioTotalGeralExport;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class RelatorioQuantitativoController extends Controller
{
    public function index(Request $request)
    {
        $eventoId = $request->integer('evento_id');
        $descricao = trim((string) $request->get('descricao', ''));
        $municipioId = $request->integer('municipio_id');
        $regiaoId = $request->integer('regiao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $periodo = $request->get('periodo', '');

        $sort = $request->get('sort', 'dia');
        $dir = $request->get('dir', 'asc') === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'acao' => 'eventos.nome',
            'momento' => 'atividades.descricao',
            'municipio' => 'municipios.nome',
            'dia' => 'atividades.dia',
            'periodo' => 'atividades.hora_inicio',
            'previstas' => 'atividades.publico_esperado',
            'presentes' => 'presentes_count',
            'avaliacoes' => 'avaliacoes_count',
        ];
        $orderByCol = $sortable[$sort] ?? 'atividades.dia';

        $query = Atividade::query()
            ->select([
                'atividades.id',
                'atividades.evento_id',
                'atividades.municipio_id',
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'atividades.hora_fim',
                'atividades.publico_esperado',
                'eventos.nome as evento_nome',
                'municipios.nome as municipio_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->withCount([
                'presencas as presentes_count' => fn ($q) => $q->where('status', 'presente'),
                'presencas as avaliacoes_count' => fn ($q) => $q->where('status', 'presente')
                    ->where('avaliacao_respondida', true),
            ])
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id');

        $query->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId));
        $query->when($municipioId, fn ($q) => $q->where('atividades.municipio_id', $municipioId));
        $query->when($regiaoId, fn ($q) => $q->where('regiaos.id', $regiaoId));
        $query->when($descricao, fn ($q) => $q->where('atividades.descricao', $descricao));

        $query->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]));
        $query->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de));
        $query->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate));

        $query->when($periodo === 'manha', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) < '12:00:00'"));
        $query->when($periodo === 'tarde', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '12:00:00'")
            ->whereRaw("CAST(atividades.hora_inicio AS time) < '18:00:00'"));
        $query->when($periodo === 'noite', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '18:00:00'"));

        $query->orderBy($orderByCol, $dir)
            ->orderBy('eventos.nome', 'asc')
            ->orderBy('atividades.dia', 'asc')
            ->orderBy('atividades.id', 'asc');

        $atividades = $query->get();

        $totalGeral = $this->buildTotalGeralData($request);

        $eventos = Evento::query()->orderBy('nome')->pluck('nome', 'id');

        $regioes = Regiao::query()->orderBy('nome')->get();

        $municipios = Municipio::query()
            ->with('estado:id,sigla')
            ->orderBy('nome')
            ->get();

        $momentos = Atividade::query()
            ->select('descricao')
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        $tab = $request->get('tab', 'momento');

        return view('relatorio-quantitativo.index',
            compact('atividades', 'totalGeral', 'eventos', 'regioes', 'municipios', 'momentos', 'sort', 'dir', 'tab'));
    }

    private function buildTotalGeralData(Request $request)
    {
        $eventoId = $request->integer('evento_id');
        $municipioId = $request->integer('municipio_id');
        $regiaoId = $request->integer('regiao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');

        $sort = $request->get('sort', 'regiao');
        $dir = $request->get('dir', 'asc') === 'asc' ? 'asc' : 'desc';

        // Query 1: Previstos por município
        $previstos = Atividade::query()
            ->select('municipio_id')
            ->selectRaw('SUM(publico_esperado) as previstos')
            ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
            ->when($municipioId, fn ($q) => $q->where('municipio_id', $municipioId))
            ->when($de && $ate, fn ($q) => $q->whereBetween('dia', [$de, $ate]))
            ->when($de && ! $ate, fn ($q) => $q->where('dia', '>=', $de))
            ->when(! $de && $ate, fn ($q) => $q->where('dia', '<=', $ate))
            ->whereNull('deleted_at')
            ->whereNotNull('evento_id')
            ->groupBy('municipio_id')
            ->get()
            ->keyBy('municipio_id');

        // Query 2: Contagens de CPF por município
        $cpfCounts = \DB::table('presencas')
            ->selectRaw('atividades.municipio_id')
            ->selectRaw('COUNT(DISTINCT CASE WHEN participantes.cpf IS NOT NULL AND participantes.cpf != \'\' THEN participantes.id END) as com_cpf')
            ->selectRaw('COUNT(DISTINCT CASE WHEN participantes.cpf IS NULL OR participantes.cpf = \'\' THEN participantes.id END) as sem_cpf')
            ->join('atividades', 'presencas.atividade_id', '=', 'atividades.id')
            ->join('inscricaos', 'presencas.inscricao_id', '=', 'inscricaos.id')
            ->join('participantes', 'inscricaos.participante_id', '=', 'participantes.id')
            ->where('presencas.status', 'presente')
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId))
            ->when($municipioId, fn ($q) => $q->where('atividades.municipio_id', $municipioId))
            ->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]))
            ->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de))
            ->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate))
            ->groupBy('atividades.municipio_id')
            ->get()
            ->keyBy('municipio_id');

        // Aplicar filtro de região
        $municipiosQuery = Municipio::query()->with('estado.regiao');
        if ($regiaoId) {
            $municipiosQuery->whereHas('estado', fn ($q) => $q->where('regiao_id', $regiaoId));
        }
        $municipios = $municipiosQuery->get()->keyBy('id');

        // Mesclar dados
        $rows = [];
        $totais = [
            'previstos' => 0,
            'com_cpf' => 0,
            'sem_cpf' => 0,
        ];

        // Linhas de municípios identificados
        foreach ($municipios as $municipioId => $municipio) {
            if ($municipioId === null) {
                continue;
            }

            $prev = $previstos->get($municipioId)?->previstos ?? 0;
            $comCpf = $cpfCounts->get($municipioId)?->com_cpf ?? 0;
            $semCpf = $cpfCounts->get($municipioId)?->sem_cpf ?? 0;
            $total = $comCpf + $semCpf;
            $pctCpf = $total > 0 ? round($comCpf / $total * 100, 2) : 0;

            $rows[] = [
                'municipio_id' => $municipioId,
                'municipio_nome' => $municipio->nome,
                'regiao' => $municipio->estado->regiao->nome ?? 'Desconhecida',
                'previstos' => (int) $prev,
                'metricas' => [
                    'cpf' => [
                        'com' => (int) $comCpf,
                        'sem' => (int) $semCpf,
                        'pct' => $pctCpf,
                    ],
                ],
            ];

            $totais['previstos'] += $prev;
            $totais['com_cpf'] += $comCpf;
            $totais['sem_cpf'] += $semCpf;
        }

        // Linha "Municípios não identificados"
        $prevNull = $previstos->get(null)?->previstos ?? 0;
        $comCpfNull = $cpfCounts->get(null)?->com_cpf ?? 0;
        $semCpfNull = $cpfCounts->get(null)?->sem_cpf ?? 0;

        if ($prevNull > 0 || $comCpfNull > 0 || $semCpfNull > 0) {
            $totalNull = $comCpfNull + $semCpfNull;
            $pctCpfNull = $totalNull > 0 ? round($comCpfNull / $totalNull * 100, 2) : 0;

            $rows[] = [
                'municipio_id' => null,
                'municipio_nome' => 'Municípios não identificados',
                'regiao' => '',
                'previstos' => (int) $prevNull,
                'metricas' => [
                    'cpf' => [
                        'com' => (int) $comCpfNull,
                        'sem' => (int) $semCpfNull,
                        'pct' => $pctCpfNull,
                    ],
                ],
                '_is_unidentified' => true,
            ];

            $totais['previstos'] += $prevNull;
            $totais['com_cpf'] += $comCpfNull;
            $totais['sem_cpf'] += $semCpfNull;
        }

        // Ordenação
        usort($rows, function ($a, $b) use ($sort, $dir) {
            if (isset($a['_is_unidentified']) || isset($b['_is_unidentified'])) {
                return 0;
            }

            $aVal = match ($sort) {
                'municipio' => $a['municipio_nome'],
                'regiao' => $a['regiao'],
                'previstos' => $a['previstos'],
                'com_cpf' => $a['metricas']['cpf']['com'],
                'sem_cpf' => $a['metricas']['cpf']['sem'],
                'pct_cpf' => $a['metricas']['cpf']['pct'],
                default => $a['regiao'].$a['municipio_nome'],
            };

            $bVal = match ($sort) {
                'municipio' => $b['municipio_nome'],
                'regiao' => $b['regiao'],
                'previstos' => $b['previstos'],
                'com_cpf' => $b['metricas']['cpf']['com'],
                'sem_cpf' => $b['metricas']['cpf']['sem'],
                'pct_cpf' => $b['metricas']['cpf']['pct'],
                default => $b['regiao'].$b['municipio_nome'],
            };

            $cmp = is_string($aVal) ? strcmp($aVal, $bVal) : $aVal <=> $bVal;

            return $dir === 'asc' ? $cmp : -$cmp;
        });

        // Ordenação padrão: Região → Município
        if ($sort === 'regiao' || $sort === 'municipio') {
            usort($rows, function ($a, $b) use ($dir) {
                if (isset($a['_is_unidentified']) || isset($b['_is_unidentified'])) {
                    return isset($a['_is_unidentified']) ? 1 : -1;
                }

                $regCmp = strcmp($a['regiao'], $b['regiao']);
                if ($regCmp !== 0) {
                    return $dir === 'asc' ? $regCmp : -$regCmp;
                }

                $munCmp = strcmp($a['municipio_nome'], $b['municipio_nome']);

                return $dir === 'asc' ? $munCmp : -$munCmp;
            });
        }

        // Linha de total
        $totalCpf = $totais['com_cpf'] + $totais['sem_cpf'];
        $pctTotal = $totalCpf > 0 ? round($totais['com_cpf'] / $totalCpf * 100, 2) : 0;

        $rows[] = [
            'municipio_id' => 'total',
            'municipio_nome' => 'TOTAL',
            'regiao' => '',
            'previstos' => $totais['previstos'],
            'metricas' => [
                'cpf' => [
                    'com' => $totais['com_cpf'],
                    'sem' => $totais['sem_cpf'],
                    'pct' => $pctTotal,
                ],
            ],
            '_is_total' => true,
        ];

        return collect($rows);
    }

    public function momentos(Request $request)
    {
        $eventoId = $request->integer('evento_id');

        $momentos = Atividade::query()
            ->select('descricao')
            ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        $municipios = Municipio::query()
            ->with('estado:id,sigla')
            ->whereIn('id',
                Atividade::query()
                    ->select('municipio_id')
                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                    ->whereNotNull('municipio_id')
                    ->distinct()
                    ->pluck('municipio_id')
            )
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id'])
            ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome_com_estado]);

        return response()->json(['momentos' => $momentos, 'municipios' => $municipios]);
    }

    public function exportarMomento(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        if ($formato === 'pdf') {
            $atividades = $this->getAtividadesData($request);

            return Pdf::view('relatorio-quantitativo.pdf-momento', compact('atividades'))
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand(35, 10, 25, 10)
                ->download('relatorio-momento-'.now()->format('Ymd_His').'.pdf');
        }

        return Excel::download(
            new RelatorioMomentoExport($request),
            'relatorio-momento-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function exportarTotalGeral(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        if ($formato === 'pdf') {
            $totalGeral = $this->buildTotalGeralData($request);

            return Pdf::view('relatorio-quantitativo.pdf-total-geral', compact('totalGeral'))
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand(35, 10, 25, 10)
                ->download('relatorio-total-geral-'.now()->format('Ymd_His').'.pdf');
        }

        return Excel::download(
            new RelatorioTotalGeralExport($request),
            'relatorio-total-geral-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    private function getAtividadesData(Request $request)
    {
        $eventoId = $request->integer('evento_id');
        $descricao = trim((string) $request->get('descricao', ''));
        $municipioId = $request->integer('municipio_id');
        $regiaoId = $request->integer('regiao_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $periodo = $request->get('periodo', '');

        $query = Atividade::query()
            ->select([
                'atividades.id',
                'atividades.evento_id',
                'atividades.municipio_id',
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'atividades.hora_fim',
                'atividades.publico_esperado',
                'eventos.nome as evento_nome',
                'municipios.nome as municipio_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->withCount([
                'presencas as presentes_count' => fn ($q) => $q->where('status', 'presente'),
                'presencas as avaliacoes_count' => fn ($q) => $q->where('status', 'presente')
                    ->where('avaliacao_respondida', true),
            ])
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id');

        $query->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId));
        $query->when($municipioId, fn ($q) => $q->where('atividades.municipio_id', $municipioId));
        $query->when($regiaoId, fn ($q) => $q->where('regiaos.id', $regiaoId));
        $query->when($descricao, fn ($q) => $q->where('atividades.descricao', $descricao));

        $query->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]));
        $query->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de));
        $query->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate));

        $query->when($periodo === 'manha', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) < '12:00:00'"));
        $query->when($periodo === 'tarde', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '12:00:00'")
            ->whereRaw("CAST(atividades.hora_inicio AS time) < '18:00:00'"));
        $query->when($periodo === 'noite', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '18:00:00'"));

        $query->orderBy('eventos.nome', 'asc')
            ->orderBy('atividades.dia', 'asc')
            ->orderBy('atividades.id', 'asc');

        return $query->get();
    }
}
