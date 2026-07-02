<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioMomentoExport;
use App\Exports\RelatorioTotalGeralExport;
use App\Http\Controllers\Concerns\ResolvesPdfBrandMargin;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class RelatorioQuantitativoController extends Controller
{
    use ResolvesPdfBrandMargin;

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
            ->when($de && $ate, fn ($q) => $q->whereBetween('dia', [$de, $ate]))
            ->when($de && ! $ate, fn ($q) => $q->where('dia', '>=', $de))
            ->when(! $de && $ate, fn ($q) => $q->where('dia', '<=', $ate))
            ->whereNull('deleted_at')
            ->whereNotNull('evento_id')
            ->groupBy('municipio_id')
            ->get()
            ->keyBy('municipio_id');

        // Query 2: Contagens de CPF e métricas demográficas por município
        $cpfCounts = \DB::table('presencas')
            ->selectRaw('atividades.municipio_id')
            ->selectRaw('COUNT(DISTINCT CASE WHEN participantes.cpf IS NOT NULL AND participantes.cpf != \'\' THEN participantes.id END) as com_cpf')
            ->selectRaw('COUNT(DISTINCT CASE WHEN participantes.cpf IS NULL OR participantes.cpf = \'\' THEN participantes.id END) as sem_cpf')
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.raca_cor = 'Branca'   THEN participantes.id END) as raca_branca")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.raca_cor = 'Parda'    THEN participantes.id END) as raca_parda")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.raca_cor = 'Preta'    THEN participantes.id END) as raca_preta")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.raca_cor = 'Amarela'  THEN participantes.id END) as raca_amarela")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.raca_cor = 'Indígena' THEN participantes.id END) as raca_indigena")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.identidade_genero ILIKE '%Mulher%' OR users.identidade_genero ILIKE '%Travesti%' THEN participantes.id END) as genero_mulheres")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.identidade_genero ILIKE '%Homem%' THEN participantes.id END) as genero_homens")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.identidade_genero IS NOT NULL AND users.identidade_genero NOT ILIKE '%Mulher%' AND users.identidade_genero NOT ILIKE '%Travesti%' AND users.identidade_genero NOT ILIKE '%Homem%' THEN participantes.id END) as genero_outros")
            ->selectRaw("COUNT(DISTINCT CASE WHEN users.pcd IS NOT NULL AND users.pcd <> '' AND users.pcd <> 'Não' THEN participantes.id END) as com_pcd")
            ->selectRaw('COUNT(DISTINCT CASE WHEN presencas.certificado_emitido = true THEN participantes.id END) as certificados_emitidos')
            ->selectRaw("COUNT(DISTINCT CASE WHEN participantes.tag = 'Rede de Ensino'   THEN participantes.id END) as tag_rede_ensino")
            ->selectRaw("COUNT(DISTINCT CASE WHEN participantes.tag = 'Movimento Social' THEN participantes.id END) as tag_movimento_social")
            ->join('atividades', 'presencas.atividade_id', '=', 'atividades.id')
            ->join('inscricaos', 'presencas.inscricao_id', '=', 'inscricaos.id')
            ->join('participantes', 'inscricaos.participante_id', '=', 'participantes.id')
            ->join('users', 'users.id', '=', 'participantes.user_id')
            ->where('presencas.status', 'presente')
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId))
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
        $pct = fn (int $n, int $total): float => $total > 0 ? round($n / $total * 100, 1) : 0.0;

        $buildMetricas = function (int $tp, ?object $counts) use ($pct): array {
            $comCpf = (int) ($counts?->com_cpf ?? 0);
            $semCpf = (int) ($counts?->sem_cpf ?? 0);
            $racaBranca = (int) ($counts?->raca_branca ?? 0);
            $racaParda = (int) ($counts?->raca_parda ?? 0);
            $racaPreta = (int) ($counts?->raca_preta ?? 0);
            $racaAmarela = (int) ($counts?->raca_amarela ?? 0);
            $racaIndigena = (int) ($counts?->raca_indigena ?? 0);
            $genMulheres = (int) ($counts?->genero_mulheres ?? 0);
            $genHomens = (int) ($counts?->genero_homens ?? 0);
            $genOutros = (int) ($counts?->genero_outros ?? 0);
            $comPcd = (int) ($counts?->com_pcd ?? 0);
            $certificados = (int) ($counts?->certificados_emitidos ?? 0);
            $tagRede = (int) ($counts?->tag_rede_ensino ?? 0);
            $tagMov = (int) ($counts?->tag_movimento_social ?? 0);

            return [
                'total_presentes' => $tp,
                'cpf' => ['com' => $comCpf, 'sem' => $semCpf, 'pct' => $pct($comCpf, $tp)],
                'raca_cor' => [
                    'branca' => $racaBranca,   'pct_branca' => $pct($racaBranca, $tp),
                    'parda' => $racaParda,    'pct_parda' => $pct($racaParda, $tp),
                    'preta' => $racaPreta,    'pct_preta' => $pct($racaPreta, $tp),
                    'amarela' => $racaAmarela,  'pct_amarela' => $pct($racaAmarela, $tp),
                    'indigena' => $racaIndigena, 'pct_indigena' => $pct($racaIndigena, $tp),
                ],
                'genero' => [
                    'mulheres' => $genMulheres, 'pct_mulheres' => $pct($genMulheres, $tp),
                    'homens' => $genHomens,   'pct_homens' => $pct($genHomens, $tp),
                    'outros' => $genOutros,   'pct_outros' => $pct($genOutros, $tp),
                ],
                'pcd' => ['n' => $comPcd,       'pct' => $pct($comPcd, $tp)],
                'certificados' => ['n' => $certificados, 'pct' => $pct($certificados, $tp)],
                'tag' => [
                    'rede_ensino' => $tagRede, 'pct_rede_ensino' => $pct($tagRede, $tp),
                    'movimento_social' => $tagMov,  'pct_movimento_social' => $pct($tagMov, $tp),
                ],
            ];
        };

        $rows = [];
        $totais = [
            'previstos' => 0,
            'com_cpf' => 0, 'sem_cpf' => 0,
            'raca_branca' => 0, 'raca_parda' => 0, 'raca_preta' => 0, 'raca_amarela' => 0, 'raca_indigena' => 0,
            'genero_mulheres' => 0, 'genero_homens' => 0, 'genero_outros' => 0,
            'pcd' => 0, 'certificados' => 0,
            'tag_rede_ensino' => 0, 'tag_movimento_social' => 0,
        ];

        // Linhas de municípios identificados
        foreach ($municipios as $mId => $municipio) {
            if ($mId === null) {
                continue;
            }

            $prev = $previstos->get($mId)?->previstos ?? 0;
            $counts = $cpfCounts->get($mId);
            $comCpf = (int) ($counts?->com_cpf ?? 0);
            $semCpf = (int) ($counts?->sem_cpf ?? 0);
            $tp = $comCpf + $semCpf;

            $rows[] = [
                'municipio_id' => $mId,
                'municipio_nome' => $municipio->nome,
                'regiao' => $municipio->estado->regiao->nome ?? 'Desconhecida',
                'previstos' => (int) $prev,
                'metricas' => $buildMetricas($tp, $counts),
            ];

            $totais['previstos'] += $prev;
            $totais['com_cpf'] += $comCpf;
            $totais['sem_cpf'] += $semCpf;
            $totais['raca_branca'] += (int) ($counts?->raca_branca ?? 0);
            $totais['raca_parda'] += (int) ($counts?->raca_parda ?? 0);
            $totais['raca_preta'] += (int) ($counts?->raca_preta ?? 0);
            $totais['raca_amarela'] += (int) ($counts?->raca_amarela ?? 0);
            $totais['raca_indigena'] += (int) ($counts?->raca_indigena ?? 0);
            $totais['genero_mulheres'] += (int) ($counts?->genero_mulheres ?? 0);
            $totais['genero_homens'] += (int) ($counts?->genero_homens ?? 0);
            $totais['genero_outros'] += (int) ($counts?->genero_outros ?? 0);
            $totais['pcd'] += (int) ($counts?->com_pcd ?? 0);
            $totais['certificados'] += (int) ($counts?->certificados_emitidos ?? 0);
            $totais['tag_rede_ensino'] += (int) ($counts?->tag_rede_ensino ?? 0);
            $totais['tag_movimento_social'] += (int) ($counts?->tag_movimento_social ?? 0);
        }

        // Linha "Municípios não identificados"
        $prevNull = $previstos->get(null)?->previstos ?? 0;
        $countsNull = $cpfCounts->get(null);
        $comCpfNull = (int) ($countsNull?->com_cpf ?? 0);
        $semCpfNull = (int) ($countsNull?->sem_cpf ?? 0);
        $tpNull = $comCpfNull + $semCpfNull;

        if ($prevNull > 0 || $tpNull > 0) {
            $rows[] = [
                'municipio_id' => null,
                'municipio_nome' => 'Municípios não identificados',
                'regiao' => '',
                'previstos' => (int) $prevNull,
                'metricas' => $buildMetricas($tpNull, $countsNull),
                '_is_unidentified' => true,
            ];

            $totais['previstos'] += $prevNull;
            $totais['com_cpf'] += $comCpfNull;
            $totais['sem_cpf'] += $semCpfNull;
            $totais['raca_branca'] += (int) ($countsNull?->raca_branca ?? 0);
            $totais['raca_parda'] += (int) ($countsNull?->raca_parda ?? 0);
            $totais['raca_preta'] += (int) ($countsNull?->raca_preta ?? 0);
            $totais['raca_amarela'] += (int) ($countsNull?->raca_amarela ?? 0);
            $totais['raca_indigena'] += (int) ($countsNull?->raca_indigena ?? 0);
            $totais['genero_mulheres'] += (int) ($countsNull?->genero_mulheres ?? 0);
            $totais['genero_homens'] += (int) ($countsNull?->genero_homens ?? 0);
            $totais['genero_outros'] += (int) ($countsNull?->genero_outros ?? 0);
            $totais['pcd'] += (int) ($countsNull?->com_pcd ?? 0);
            $totais['certificados'] += (int) ($countsNull?->certificados_emitidos ?? 0);
            $totais['tag_rede_ensino'] += (int) ($countsNull?->tag_rede_ensino ?? 0);
            $totais['tag_movimento_social'] += (int) ($countsNull?->tag_movimento_social ?? 0);
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
                'total_presentes' => $a['metricas']['total_presentes'],
                'com_cpf' => $a['metricas']['cpf']['com'],
                'sem_cpf' => $a['metricas']['cpf']['sem'],
                'pct_cpf' => $a['metricas']['cpf']['pct'],
                default => $a['regiao'].$a['municipio_nome'],
            };

            $bVal = match ($sort) {
                'municipio' => $b['municipio_nome'],
                'regiao' => $b['regiao'],
                'previstos' => $b['previstos'],
                'total_presentes' => $b['metricas']['total_presentes'],
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

        // Linha de total — pcts calculados sobre os totais acumulados
        $tpTotal = $totais['com_cpf'] + $totais['sem_cpf'];
        $totalCounts = (object) [
            'com_cpf' => $totais['com_cpf'],
            'sem_cpf' => $totais['sem_cpf'],
            'raca_branca' => $totais['raca_branca'],
            'raca_parda' => $totais['raca_parda'],
            'raca_preta' => $totais['raca_preta'],
            'raca_amarela' => $totais['raca_amarela'],
            'raca_indigena' => $totais['raca_indigena'],
            'genero_mulheres' => $totais['genero_mulheres'],
            'genero_homens' => $totais['genero_homens'],
            'genero_outros' => $totais['genero_outros'],
            'com_pcd' => $totais['pcd'],
            'certificados_emitidos' => $totais['certificados'],
            'tag_rede_ensino' => $totais['tag_rede_ensino'],
            'tag_movimento_social' => $totais['tag_movimento_social'],
        ];

        $rows[] = [
            'municipio_id' => 'total',
            'municipio_nome' => 'TOTAL',
            'regiao' => '',
            'previstos' => $totais['previstos'],
            'metricas' => $buildMetricas($tpTotal, $totalCounts),
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
            $marginTop = $this->brandImageMarginMm('images/Alfa-Eja Header.png', 297, 40);

            return Pdf::view('relatorio-quantitativo.pdf-momento', compact('atividades'))
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand($marginTop, 10, 25, 10)
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
            $dimensoes = $request->input('dimensoes', []);
            $marginTop = $this->brandImageMarginMm('images/Alfa-Eja Header.png', 297, 40);

            return Pdf::view('relatorio-quantitativo.pdf-total-geral', compact('totalGeral', 'dimensoes'))
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand($marginTop, 10, 25, 10)
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
