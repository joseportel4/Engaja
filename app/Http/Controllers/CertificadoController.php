<?php

namespace App\Http\Controllers;

use App\Mail\CertificadoEmitidoMail;
use App\Models\Certificado;
use App\Models\Evento;
use App\Models\ModeloCertificado;
use App\Models\Participante;
use App\Models\Presenca;
use App\Support\CargaHoraria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;

class CertificadoController extends Controller
{
    public function emitir(Request $request)
    {

        $sessionKey = $request->input('session_key');
        if ($sessionKey) {
            $payload = session($sessionKey);
            if (!$payload) {
                return redirect()->route('eventos.index')->with('error', 'Sessão expirada. Tente novamente.');
            }
            $request->merge([
                'modelo_id' => $payload['modelo_id'],
                'eventos'   => $payload['eventos'],
            ]);
        }

        $data = $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'eventos' => ['required'],
        ]);

        $eventosIds = $data['eventos'];
        if (is_string($eventosIds)) {
            $eventosIds = array_filter(explode(',', $eventosIds));
        }
        if (is_array($eventosIds)) {
            $eventosIds = array_map('intval', $eventosIds);
        } else {
            $eventosIds = [];
        }
        $eventosIds = array_unique(array_filter($eventosIds));
        if (empty($eventosIds)) {
            return back()->with('error', 'Selecione ao menos uma ação pedagógica.');
        }

        $modelo = ModeloCertificado::findOrFail($data['modelo_id']);

        $eventos = Evento::with(['presencas.inscricao.participante.user', 'presencas.atividade'])
            ->whereIn('id', $eventosIds)
            ->get();

        $created = 0;
        $skippedZeroWorkload = 0;
        $paraNotificar = [];
        foreach ($eventos as $evento) {
            // Somat?rio por participante para este evento, apenas presen?as confirmadas ainda n?o certificadas
            $presencasEvento = $evento->presencas
                ->filter(function ($presenca) {
                    return ($presenca->status ?? null) === 'presente'
                        && ! $presenca->certificado_emitido
                        && $presenca->inscricao?->participante?->id;
                });

            $presencasPorParticipante = $presencasEvento
                ->groupBy(fn ($p) => $p->inscricao->participante->id);

            foreach ($presencasPorParticipante as $participanteId => $presencas) {
                $participante = $presencas->first()->inscricao?->participante;
                if (! $participante || ! $participante->user) {
                    continue;
                }

                $cargaTotal = (int) $presencas->sum(function ($p) {
                    return (int) ($p->atividade?->carga_horaria ?? 0);
                });

                if ($cargaTotal <= 0) {
                    $skippedZeroWorkload++;

                    continue;
                }

                $map = [
                    '%participante%' => $participante->user->name,
                    '%acao%' => $evento->nome,
                    '%carga_horaria%' => CargaHoraria::formatMinutos($cargaTotal),
                ];

                $textoFrente = $this->renderPlaceholders($modelo->texto_frente ?? '', $map);
                $textoVerso = $this->renderPlaceholders($modelo->texto_verso ?? '', $map);

                $cert = Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id' => $participante->id,
                    'evento_nome' => $evento->nome,
                    'codigo_validacao' => Str::uuid()->toString(),
                    'ano' => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente' => $textoFrente,
                    'texto_verso' => $textoVerso,
                    'carga_horaria' => $cargaTotal,
                ]);
                if (! empty($participante->user?->email)) {
                    $paraNotificar[] = [$participante->user->email, $participante->user->name, $evento->nome, $cert->id];
                }

                // Marca todas as presen?as deste participante no evento como certificadas
                foreach ($presencas as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        // $this->notificarLote($paraNotificar);

        $message = "{$created} certificado(s) emitidos com sucesso.";
        if ($skippedZeroWorkload > 0) {
            $message .= " {$skippedZeroWorkload} certificado(s) não emitido(s) por carga horária total igual a 0.";
        }

        if ($sessionKey) {
            session()->forget($sessionKey);
        }

        return redirect()
            ->route('eventos.index')
            ->with('success', $message);
    }

    public function prepararEmissao(Request $request)
    {
        $data = $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'eventos'   => ['required'],
        ]);

        $sessionKey = 'emissao_certificados_' . Str::uuid();
        session([$sessionKey => [
            'modelo_id' => $data['modelo_id'],
            'eventos'   => $data['eventos']
        ]]);

        return redirect()->route('certificados.emitir.preview_lista', ['session_key' => $sessionKey]);
    }

    public function previewLista(Request $request)
    {
        $sessionKey = $request->query('session_key');
        $payload = session($sessionKey);

        if (!$payload) {
            return redirect()->route('eventos.index')->with('error', 'Sessão expirada. Refaça a seleção das ações pedagógicas.');
        }

        $modelo = ModeloCertificado::findOrFail($payload['modelo_id']);

        $eventosIds = array_unique(array_filter(array_map('intval', explode(',', $payload['eventos']))));
        $eventos = Evento::with(['presencas.inscricao.participante.user', 'presencas.atividade'])
            ->whereIn('id', $eventosIds)
            ->get();

        $previewData = collect();
        $skippedZeroWorkload = 0;

        foreach ($eventos as $evento) {
            $presencasEvento = $evento->presencas->filter(function ($presenca) {
                return ($presenca->status ?? null) === 'presente'
                    && !$presenca->certificado_emitido
                    && $presenca->inscricao?->participante?->id;
            });

            $presencasPorParticipante = $presencasEvento->groupBy(fn ($p) => $p->inscricao->participante->id);

            foreach ($presencasPorParticipante as $participanteId => $presencas) {
                $participante = $presencas->first()->inscricao?->participante;
                if (!$participante || !$participante->user) continue;

                $cargaTotal = (int) $presencas->sum(fn($p) => (int) ($p->atividade?->carga_horaria ?? 0));

                if ($cargaTotal <= 0) {
                    $skippedZeroWorkload++;
                    continue;
                }

                $previewData->push([
                    'nome'          => $participante->user->name,
                    'email'         => $participante->user->email,
                    'cpf'           => $participante->cpf ?? '-',
                    'carga_horaria' => CargaHoraria::formatMinutos($cargaTotal),
                    'evento_nome'   => $evento->nome
                ]);
            }
        }

        //paginação
        $perPage = 50;
        $page = (int) max(1, $request->query('page', 1));
        $slice = $previewData->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $previewData->count(),
            $perPage,
            $page,
            [
                'path'  => route('certificados.emitir.preview_lista'),
                'query' => ['session_key' => $sessionKey],
            ]
        );

        return view('certificados.preview_lista', [
            'paginator'           => $paginator,
            'sessionKey'          => $sessionKey,
            'totalProntos'        => $previewData->count(),
            'skippedZeroWorkload' => $skippedZeroWorkload,
            'modelo'              => $modelo,
        ]);
    }

    private function notificarLote(array $paraNotificar): void
    {
        // Limitamos a ~2 e-mails/segundo (100 e-mails em ~50s) e aguardamos completar 60s antes do próximo bloco.
        $chunks = collect($paraNotificar)->chunk(100);
        foreach ($chunks as $chunkIndex => $chunk) {
            $base = Carbon::now()->addSeconds($chunkIndex * 60);
            foreach ($chunk->values() as $i => [$email, $nome, $acao, $certId]) {
                // Dois e-mails por segundo: delay incremental a cada par.
                $pairDelay = intdiv($i, 2); // 0,0,1,1,2,2...
                $scheduleAt = $base->copy()->addSeconds($pairDelay);
                Mail::to($email)->later($scheduleAt, new CertificadoEmitidoMail($nome, $acao, $certId));
            }
        }
    }

    public function emitirPorParticipantes(Request $request)
    {
        $data = $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'participantes' => ['sometimes', 'array'],
            'participantes.*' => ['integer'],
            'select_all_pages' => ['sometimes', 'boolean'],
        ]);

        $modelo = ModeloCertificado::findOrFail($data['modelo_id']);
        $participantesIds = array_unique(array_filter($data['participantes'] ?? []));
        $selectAllPages = (bool) ($data['select_all_pages'] ?? false);

        // Busca presen?as pendentes, filtrando se houver sele??o
        $presencasPendentes = Presenca::with(['atividade.evento', 'inscricao.participante.user'])
            ->where('status', 'presente')
            ->where('certificado_emitido', false)
            ->when(! empty($participantesIds) && ! $selectAllPages, function ($q) use ($participantesIds) {
                $q->whereHas('inscricao.participante', fn ($sub) => $sub->whereIn('id', $participantesIds));
            })
            ->whereHas('inscricao.participante') // garante participante
            ->get()
            ->filter(fn ($p) => $p->atividade?->evento); // garante evento carregado

        $created = 0;
        $skippedZeroWorkload = 0;
        $paraNotificar = [];

        // Agrupa por participante para emitir um cert por evento
        $presencasPorParticipante = $presencasPendentes->groupBy(fn ($p) => $p->inscricao->participante->id);

        foreach ($presencasPorParticipante as $participanteId => $presencasDoParticipante) {
            $participante = $presencasDoParticipante->first()->inscricao->participante;
            if (! $participante || ! $participante->user) {
                continue;
            }

            $presencasPorEvento = $presencasDoParticipante->groupBy(fn ($p) => $p->atividade->evento_id);

            foreach ($presencasPorEvento as $eventoId => $presencasEvento) {
                $evento = $presencasEvento->first()->atividade->evento;
                if (! $evento) {
                    continue;
                }

                $cargaTotal = (int) $presencasEvento->sum(function ($p) {
                    return (int) ($p->atividade?->carga_horaria ?? 0);
                });

                if ($cargaTotal <= 0) {
                    $skippedZeroWorkload++;

                    continue;
                }

                $map = [
                    '%participante%' => $participante->user->name,
                    '%acao%' => $evento->nome,
                    '%carga_horaria%' => CargaHoraria::formatMinutos($cargaTotal),
                ];

                $textoFrente = $this->renderPlaceholders($modelo->texto_frente ?? '', $map);
                $textoVerso = $this->renderPlaceholders($modelo->texto_verso ?? '', $map);

                $cert = Certificado::create([
                    'modelo_certificado_id' => $modelo->id,
                    'participante_id' => $participante->id,
                    'evento_nome' => $evento->nome,
                    'codigo_validacao' => Str::uuid()->toString(),
                    'ano' => (int) ($evento->data_inicio ? date('Y', strtotime($evento->data_inicio)) : date('Y')),
                    'texto_frente' => $textoFrente,
                    'texto_verso' => $textoVerso,
                    'carga_horaria' => $cargaTotal,
                ]);
                if (! empty($participante->user?->email)) {
                    $paraNotificar[] = [$participante->user->email, $participante->user->name, $evento->nome, $cert->id];
                }

                foreach ($presencasEvento as $presenca) {
                    $presenca->certificado_emitido = true;
                    $presenca->save();
                }

                $created++;
            }
        }

        // $this->notificarLote($paraNotificar);

        $message = "{$created} certificado(s) emitidos com sucesso.";
        if ($skippedZeroWorkload > 0) {
            $message .= " {$skippedZeroWorkload} certificado(s) não emitido(s) por carga horária total igual a 0.";
        }

        return redirect()
            ->back()
            ->with('success', $message);
    }

    private function renderPlaceholders(string $texto, array $map): string
    {
        return strtr($texto, $map);
    }

    public function show(Certificado $certificado)
    {
        $user = auth()->user();
        $isOwner = $certificado->participante_id === optional($user->participante)->id;
        $isAdmin = $user->hasAnyRole(['administrador', 'gestor']);
        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }
        $certificado->load('modelo');

        return view('certificados.show', compact('certificado'));
    }

    public function validar(string $codigo)
    {
        $certificado = Certificado::with('modelo', 'participante.user')
            ->where('codigo_validacao', $codigo)
            ->firstOrFail();

        return view('certificados.validacao', compact('certificado'));
    }

    public function download(Certificado $certificado)
    {
        $user = auth()->user();
        $isOwner = $certificado->participante_id === optional($user->participante)->id;
        $isAdmin = $user->hasAnyRole(['administrador', 'gestor']);
        if (! $isOwner && ! $isAdmin) {
            abort(403);
        }

        $certificado->load('modelo');
        $pdf = app('dompdf.wrapper');
        // Reduz DPI para gerar arquivo menor (objetivo ~1MB) e permite imagens remotas
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 72,
            'defaultMediaType' => 'print',
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('certificados.pdf', ['certificado' => $certificado]);
        $fileName = 'certificado-'.$certificado->id.'.pdf';

        return $pdf->download($fileName);
    }

    public function emitidos(Request $request)
    {
        $filtroParticipante = trim($request->query('participante', ''));
        $filtroAcao = trim($request->query('acao', ''));

        $query = Certificado::with(['participante.user', 'modelo']);

        if ($filtroParticipante) {
            $query->whereHas('participante.user', function ($q) use ($filtroParticipante) {
                $q->where('name', 'ilike', "%{$filtroParticipante}%");
            });
        }

        if ($filtroAcao) {
            $query->where('evento_nome', 'ilike', "%{$filtroAcao}%");
        }

        $certificados = $query->latest()
            ->paginate(20)
            ->appends([
                'participante' => $filtroParticipante,
                'acao' => $filtroAcao,
            ]);

        return view('certificados.emitidos', compact('certificados', 'filtroParticipante', 'filtroAcao'));
    }

    public function edit(Certificado $certificado)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['administrador', 'gestor'])) {
            abort(403);
        }

        $certificado->load(['participante.user', 'modelo']);

        return view('certificados.edit', compact('certificado'));
    }

    public function update(Request $request, Certificado $certificado)
    {
        $user = auth()->user();
        if (! $user->hasAnyRole(['administrador', 'gestor'])) {
            abort(403);
        }

        $data = $request->validate([
            'texto_frente' => ['required', 'string'],
            'texto_verso' => ['nullable', 'string'],
        ]);

        $certificado->update([
            'texto_frente' => $data['texto_frente'],
            'texto_verso' => $data['texto_verso'] ?? null,
        ]);

        return redirect()
            ->route('certificados.emitidos')
            ->with('success', 'Certificado atualizado com sucesso.');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'modelo_id' => ['required', 'exists:modelo_certificados,id'],
            'eventos' => ['nullable', 'string'],
        ]);

        $modelo = ModeloCertificado::findOrFail($request->modelo_id);
        $eventos = collect(explode(',', (string) $request->eventos))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $eventoNome = 'Ação pedagógica';
        if ($eventos->count()) {
            $evento = Evento::find($eventos->first());
            if ($evento) {
                $eventoNome = $evento->nome;
            }
        }

        $map = [
            '%participante%' => '[NOME DO PARTICIPANTE]',
            '%acao%' => '[NOME DA AÇÃO PEDAGÓGICA]',
            '%carga_horaria%' => CargaHoraria::formatMinutos(600),
        ];

        $certificado = new Certificado;
        $certificado->modelo = $modelo;
        $certificado->texto_frente = strtr($modelo->texto_frente ?? '', $map);
        $certificado->texto_verso = strtr($modelo->texto_verso ?? '', $map);
        $certificado->evento_nome = $eventoNome;
        $certificado->codigo_validacao = null;
        $certificado->carga_horaria = 600;

        $pdf = app('dompdf.wrapper');
        $pdf->setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'dpi' => 72,
            'defaultMediaType' => 'print',
        ]);
        $pdf->setPaper('a4', 'landscape');
        $pdf->loadView('certificados.pdf', ['certificado' => $certificado]);

        return $pdf->stream('certificado-preview.pdf');
    }
}
