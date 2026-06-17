<?php

namespace App\Http\Controllers;

use App\Exports\PainelGerencialExport;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Services\PainelGerencialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class PainelGerencialController extends Controller
{
    public function __construct(private PainelGerencialService $service) {}

    public function index(Request $request)
    {
        $payload = $this->service->buildPayload($request);

        $eventos = Evento::query()->orderBy('nome')->pluck('nome', 'id');
        $regioes = Regiao::query()->orderBy('nome')->get();
        $municipios = Municipio::query()->with('estado:id,sigla')->orderBy('nome')->get();
        $momentos = Atividade::query()
            ->select('descricao')
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        return view('painel-gerencial.index', array_merge($payload, compact('eventos', 'regioes', 'municipios', 'momentos')));
    }

    public function dados(Request $request): JsonResponse
    {
        return response()->json($this->service->buildPayload($request));
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        if ($formato === 'pdf') {
            ini_set('memory_limit', config('dashboard.pdf.memory_limit'));

            $payload = $this->service->buildPayload($request);

            // O header do macro é renderizado a width:100% (escala com a página),
            // então a margem superior precisa acompanhar a proporção da imagem.
            // A4 paisagem tem 297mm de largura. O rodapé tem largura fixa no macro,
            // logo a margem inferior padrão (25mm) já o acomoda.
            $marginTop = $this->brandImageMarginMm('images/Alfa-Eja Header.png', 297, 40);

            return Pdf::view('painel-gerencial.pdf', $payload)
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand($marginTop, 10, 25, 10)
                ->download('painel-gerencial-'.now()->format('Ymd_His').'.pdf');
        }

        return Excel::download(
            new PainelGerencialExport($request),
            'painel-gerencial-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    /**
     * Calcula a margem (em mm) necessária para acomodar uma imagem de
     * cabeçalho/rodapé que é renderizada a 100% da largura da página pelo
     * Puppeteer. A altura renderizada = largura da página × (altura/largura da
     * imagem); somamos uma folga de 3mm. Cai no fallback se a imagem não existir
     * ou não puder ser lida, mantendo o comportamento correto para qualquer
     * proporção de imagem.
     */
    private function brandImageMarginMm(string $relativePath, float $pageWidthMm, int $fallback): int
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            return $fallback;
        }

        $dimensoes = @getimagesize($path);
        if ($dimensoes === false || empty($dimensoes[0])) {
            return $fallback;
        }

        [$largura, $altura] = $dimensoes;
        $alturaMm = $pageWidthMm * ($altura / $largura);

        return (int) ceil($alturaMm + 3);
    }

    public function momentos(Request $request): JsonResponse
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
}
