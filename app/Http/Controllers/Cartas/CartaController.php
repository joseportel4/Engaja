<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaEvento;
use App\Models\Cartas\CartaMensagem;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\User;
use App\Notifications\Cartas\AjusteSolicitadoNotification;
use App\Notifications\Cartas\CartaRecebidaNotification;
use App\Services\Cartas\CartaTimbradoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\LaravelPdf\Facades\Pdf;
use setasign\Fpdi\Fpdi;

class CartaController extends Controller
{
    public function __construct(private CartaTimbradoService $timbrado) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        if ($this->isGestor($user)) {
            return $this->gestorDashboard($request);
        }

        return $this->voluntarioDashboard($request);
    }

    public function show(Request $request, Carta $carta): View
    {
        $this->authorizeCartaAccess($request, $carta);

        $carta->load([
            'educando.user',
            'educando.municipio.estado',
            'voluntario.participante.municipio.estado',
            'ultimaMensagem',
            'mensagens.remetenteUsuario.participante.municipio.estado',
            'mensagens.remetenteParticipante.user',
            'mensagens.remetenteParticipante.municipio.estado',
            'mensagens.destinatarioUsuario.participante.municipio.estado',
            'mensagens.destinatarioParticipante.user',
            'mensagens.destinatarioParticipante.municipio.estado',
        ]);

        $gestor = $this->isGestor($request->user());
        if (! $gestor) {
            $this->markConversationAsRead($request->user(), $carta);
            $carta->load('mensagens');
        }

        $engajaUsers = $gestor ? $this->engajaUsersQuery()->get() : collect();
        $voluntarios = $gestor ? $this->voluntariosQuery()->get() : collect();

        return view('cartas.cartas.show', compact('carta', 'gestor', 'engajaUsers', 'voluntarios'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $data = $request->validate([
            'remetente_user_id' => ['required', 'exists:users,id'],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'remetente_user_id.required' => 'Selecione o remetente.',
            'arquivo.required' => 'Selecione o arquivo da carta.',
            'arquivo.mimes' => 'Envie um arquivo em PDF.',
        ]);

        $remetente = User::with('participante')->findOrFail($data['remetente_user_id']);
        $participante = $remetente->participante;

        if (! $participante) {
            return back()->withErrors(['remetente_user_id' => 'O remetente selecionado nao possui participante vinculado.'])->withInput();
        }

        $eventosCartas = Evento::where('is_cartas', true)->get();
        abort_if($eventosCartas->count() > 1, 500, 'Configuracao invalida: mais de uma acao de cartas esta marcada como ativa.');
        $eventoCartas = $eventosCartas->first();

        if (! $eventoCartas) {
            return back()->withErrors(['remetente_user_id' => 'Nenhuma acao de cartas esta configurada.'])->withInput();
        }

        $voluntario = $this->selectVoluntario();
        if (! $voluntario) {
            return back()->withErrors(['destinatario' => 'Nao ha voluntarios disponiveis para receber a carta.'])->withInput();
        }

        $file = $request->file('arquivo');

        $carta = DB::transaction(function () use ($request, $participante, $voluntario, $file, $eventoCartas) {
            if (! $participante->inscricoes()->where('evento_id', $eventoCartas->id)->exists()) {
                Inscricao::create([
                    'evento_id' => $eventoCartas->id,
                    'participante_id' => $participante->id,
                ]);
            }

            $codigo = $this->nextCodigo();

            $carta = Carta::create([
                'codigo' => $codigo,
                'educando_participante_id' => $participante->id,
                'voluntario_user_id' => $voluntario->id,
                'municipio_id' => $participante->municipio_id,
                'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
                'distribuida_em' => now(),
                'criada_por' => $request->user()->id,
                'atualizada_por' => $request->user()->id,
            ]);

            $path = $file->store("cartas/{$carta->id}/originais", 'local');

            $mensagem = CartaMensagem::create([
                'carta_id' => $carta->id,
                'rodada' => 1,
                'remetente_participante_id' => $participante->id,
                'destinatario_user_id' => $voluntario->id,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
                'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
                'status' => CartaMensagem::STATUS_APROVADA,
                'anexo_original_path' => $path,
                'anexo_original_nome' => $file->getClientOriginalName(),
                'anexo_original_mime' => $file->getClientMimeType(),
                'anexo_original_tamanho' => $file->getSize(),
                'enviada_em' => now(),
                'criada_por' => $request->user()->id,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $carta->id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_CRIADA,
                'dados_depois' => [
                    'codigo' => $codigo,
                    'voluntario_user_id' => $voluntario->id,
                    'educando_participante_id' => $participante->id,
                ],
            ]);

            return $carta;
        });

        $voluntario->notify(new CartaRecebidaNotification($carta->load('mensagens')));

        return redirect()->route('cartas.dashboard')->with('status', 'Carta enviada para o voluntario.');
    }

    public function storeMessage(Request $request, Carta $carta): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'arquivo.required' => 'Selecione o arquivo da carta.',
            'arquivo.mimes' => 'Envie um arquivo em PDF.',
        ]);

        $carta->loadMissing(['educando.user', 'voluntario', 'ultimaMensagem']);
        $participante = $carta->educando;
        $voluntario = $carta->voluntario;

        abort_unless($participante && $voluntario, 422);

        if (! $carta->podeEducandoEnviar()) {
            return back()->withErrors(['arquivo' => 'Aguarde a resposta do voluntário antes de enviar uma nova carta.']);
        }

        $file = $request->file('arquivo');

        DB::transaction(function () use ($request, $carta, $participante, $voluntario, $file) {
            $rodada = ((int) $carta->mensagens()->max('rodada')) + 1;
            $path = $file->store("cartas/{$carta->id}/originais", 'local');

            $mensagem = CartaMensagem::create([
                'carta_id' => $carta->id,
                'rodada' => $rodada,
                'remetente_participante_id' => $participante->id,
                'destinatario_user_id' => $voluntario->id,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
                'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
                'status' => CartaMensagem::STATUS_APROVADA,
                'anexo_original_path' => $path,
                'anexo_original_nome' => $file->getClientOriginalName(),
                'anexo_original_mime' => $file->getClientMimeType(),
                'anexo_original_tamanho' => $file->getSize(),
                'enviada_em' => now(),
                'criada_por' => $request->user()->id,
                'atualizada_por' => $request->user()->id,
            ]);

            $carta->update([
                'status' => Carta::STATUS_RESPONDIDA,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $carta->id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
            ]);
        });

        $voluntario->notify(new CartaRecebidaNotification($carta->load('mensagens')));

        return redirect()->route('cartas.cartas.show', $carta)->with('status', 'Carta adicionada.');
    }

    public function respond(Request $request, Carta $carta): RedirectResponse
    {
        abort_unless($carta->voluntario_user_id === $request->user()->id, 403);

        $carta->loadMissing('ultimaMensagem');

        if (! $carta->podeVoluntarioEnviar()) {
            return back()->withErrors(['modo_resposta' => 'Aguarde a próxima carta do educando antes de responder novamente.']);
        }

        $data = $request->validate([
            'modo_resposta' => ['required', Rule::in(['digitada', 'anexo_manuscrito'])],
            'texto' => ['nullable', 'required_if:modo_resposta,digitada', 'string', 'max:12000'],
            'arquivo' => ['nullable', 'required_if:modo_resposta,anexo_manuscrito', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'modo_resposta.required' => 'Selecione como deseja responder.',
            'texto.required_if' => 'Digite a carta antes de enviar.',
            'arquivo.required_if' => 'Anexe a carta em PDF antes de enviar.',
            'arquivo.mimes' => 'Envie um arquivo em PDF.',
        ]);

        DB::transaction(function () use ($request, $carta, $data) {
            $rodada = ((int) $carta->mensagens()->max('rodada')) + 1;
            $path = null;
            $file = $request->file('arquivo');

            if ($file) {
                $path = $file->store("cartas/{$carta->id}/originais", 'local');
            }

            $mensagem = CartaMensagem::create([
                'carta_id' => $carta->id,
                'rodada' => $rodada,
                'remetente_user_id' => $request->user()->id,
                'destinatario_participante_id' => $carta->educando_participante_id,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
                'canal_entrada' => $data['modo_resposta'] === 'digitada' ? CartaMensagem::CANAL_DIGITADA : CartaMensagem::CANAL_ANEXO_MANUSCRITO,
                'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
                'texto' => $data['texto'] ?? null,
                'texto_resumo' => isset($data['texto']) ? str($data['texto'])->limit(500)->toString() : null,
                'anexo_original_path' => $path,
                'anexo_original_nome' => $file?->getClientOriginalName(),
                'anexo_original_mime' => $file?->getClientMimeType(),
                'anexo_original_tamanho' => $file?->getSize(),
                'enviada_em' => now(),
                'criada_por' => $request->user()->id,
                'atualizada_por' => $request->user()->id,
            ]);

            if ($data['modo_resposta'] === 'digitada') {
                $this->timbrado->aplicar($mensagem);
            }

            $carta->update([
                'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $carta->id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
            ]);
        });

        return redirect()->route('cartas.dashboard')->with('cartas_thanks', true);
    }

    public function storeVolunteerLetter(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole('cartas_voluntario') || $user->can('cartas.responder'), 403);

        $data = $request->validate([
            'destinatario_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('sistema_origem', User::SISTEMA_ENGAJA),
            ],
            'arquivo' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'destinatario_user_id.required' => 'Selecione o destinatario.',
            'arquivo.required' => 'Selecione o arquivo da carta.',
            'arquivo.mimes' => 'Envie um arquivo em PDF.',
        ]);

        $destinatario = User::with('participante')->findOrFail($data['destinatario_user_id']);
        if (! $destinatario->participante) {
            return back()->withErrors(['destinatario_user_id' => 'O destinatario selecionado nao possui participante vinculado.'])->withInput();
        }

        $file = $request->file('arquivo');

        $carta = DB::transaction(function () use ($user, $destinatario, $file) {
            $carta = Carta::create([
                'codigo' => $this->nextCodigo(),
                'educando_participante_id' => $destinatario->participante->id,
                'voluntario_user_id' => $user->id,
                'municipio_id' => $destinatario->participante->municipio_id,
                'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
                'distribuida_em' => now(),
                'criada_por' => $user->id,
                'atualizada_por' => $user->id,
            ]);

            $path = $file->store("cartas/{$carta->id}/originais", 'local');

            $mensagem = CartaMensagem::create([
                'carta_id' => $carta->id,
                'rodada' => 1,
                'remetente_user_id' => $user->id,
                'destinatario_participante_id' => $destinatario->participante->id,
                'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
                'canal_entrada' => CartaMensagem::CANAL_ANEXO_MANUSCRITO,
                'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
                'anexo_original_path' => $path,
                'anexo_original_nome' => $file->getClientOriginalName(),
                'anexo_original_mime' => $file->getClientMimeType(),
                'anexo_original_tamanho' => $file->getSize(),
                'enviada_em' => now(),
                'criada_por' => $user->id,
                'atualizada_por' => $user->id,
            ]);

            CartaEvento::create([
                'carta_id' => $carta->id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $user->id,
                'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
            ]);

            return $carta;
        });

        return redirect()->route('cartas.dashboard')->with('cartas_thanks', true);
    }

    public function destroy(Request $request, Carta $carta): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $carta->delete();

        return back()->with('status', 'Carta removida.');
    }

    public function approveMessage(Request $request, CartaMensagem $mensagem): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $mensagem->load('carta');
        abort_unless($mensagem->status === CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO, 422);

        DB::transaction(function () use ($request, $mensagem) {
            $before = [
                'mensagem_status' => $mensagem->status,
                'carta_status' => $mensagem->carta->status,
            ];

            $mensagem->update([
                'status' => CartaMensagem::STATUS_APROVADA,
                'verificada_por' => $request->user()->id,
                'verificada_em' => now(),
                'parecer_verificacao' => null,
                'atualizada_por' => $request->user()->id,
            ]);

            $mensagem->carta->update([
                'status' => Carta::STATUS_RESPONDIDA,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $mensagem->carta_id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_MENSAGEM_VERIFICADA,
                'dados_antes' => $before,
                'dados_depois' => [
                    'mensagem_status' => CartaMensagem::STATUS_APROVADA,
                    'carta_status' => Carta::STATUS_RESPONDIDA,
                ],
            ]);
        });

        return back()->with('status', 'Resposta aprovada.');
    }

    public function requestMessageAdjustment(Request $request, CartaMensagem $mensagem): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $mensagem->load('carta');
        abort_unless($mensagem->status === CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO, 422);

        $data = $request->validate([
            'parecer_verificacao' => ['nullable', 'string', 'max:2000'],
        ], [
            'parecer_verificacao.max' => 'O parecer deve ter no maximo 2000 caracteres.',
        ]);

        DB::transaction(function () use ($request, $mensagem, $data) {
            $before = [
                'mensagem_status' => $mensagem->status,
                'carta_status' => $mensagem->carta->status,
            ];

            $mensagem->update([
                'status' => CartaMensagem::STATUS_AJUSTE_SOLICITADO,
                'verificada_por' => $request->user()->id,
                'verificada_em' => now(),
                'parecer_verificacao' => $data['parecer_verificacao'] ?? null,
                'atualizada_por' => $request->user()->id,
            ]);

            $mensagem->carta->update([
                'status' => Carta::STATUS_AGUARDANDO_AJUSTE,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $mensagem->carta_id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_AJUSTE_SOLICITADO,
                'dados_antes' => $before,
                'dados_depois' => [
                    'mensagem_status' => CartaMensagem::STATUS_AJUSTE_SOLICITADO,
                    'carta_status' => Carta::STATUS_AGUARDANDO_AJUSTE,
                    'parecer_verificacao' => $data['parecer_verificacao'] ?? null,
                ],
            ]);
        });

        $mensagem->loadMissing('carta');
        $voluntario = $mensagem->carta->voluntario;
        $voluntario?->notify(new AjusteSolicitadoNotification($mensagem));

        return back()->with('status', 'Ajuste solicitado ao voluntário.');
    }

    public function updateAdjustedMessage(Request $request, CartaMensagem $mensagem): RedirectResponse
    {
        $mensagem->load('carta');

        abort_unless($mensagem->isEditavelPor($request->user()), 403);
        abort_unless($mensagem->status === CartaMensagem::STATUS_AJUSTE_SOLICITADO, 422);

        $data = $request->validate([
            'modo_resposta' => ['required', Rule::in(['digitada', 'anexo_manuscrito'])],
            'texto' => ['nullable', 'required_if:modo_resposta,digitada', 'string', 'max:12000'],
            'arquivo' => ['nullable', 'required_if:modo_resposta,anexo_manuscrito', 'file', 'mimes:pdf', 'max:10240'],
        ], [
            'modo_resposta.required' => 'Selecione como deseja ajustar a resposta.',
            'texto.required_if' => 'Digite a carta ajustada antes de enviar.',
            'arquivo.required_if' => 'Anexe a carta ajustada antes de enviar.',
            'arquivo.mimes' => 'Envie um arquivo em PDF.',
        ]);

        DB::transaction(function () use ($request, $mensagem, $data) {
            $before = [
                'mensagem_status' => $mensagem->status,
                'carta_status' => $mensagem->carta->status,
                'parecer_verificacao' => $mensagem->parecer_verificacao,
            ];

            $file = $request->file('arquivo');
            $path = null;

            if ($file) {
                $path = $file->store("cartas/{$mensagem->carta_id}/originais", 'local');
            }

            $updates = [
                'canal_entrada' => $data['modo_resposta'] === 'digitada'
                    ? CartaMensagem::CANAL_DIGITADA
                    : CartaMensagem::CANAL_ANEXO_MANUSCRITO,
                'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
                'texto' => $data['modo_resposta'] === 'digitada' ? ($data['texto'] ?? null) : null,
                'texto_resumo' => $data['modo_resposta'] === 'digitada' && isset($data['texto'])
                    ? str($data['texto'])->limit(500)->toString()
                    : null,
                'parecer_verificacao' => null,
                'verificada_por' => null,
                'verificada_em' => null,
                'enviada_em' => now(),
                'atualizada_por' => $request->user()->id,
            ];

            if ($file) {
                $updates = array_merge($updates, [
                    'anexo_original_path' => $path,
                    'anexo_original_nome' => $file->getClientOriginalName(),
                    'anexo_original_mime' => $file->getClientMimeType(),
                    'anexo_original_tamanho' => $file->getSize(),
                ]);
            } else {
                $updates = array_merge($updates, [
                    'anexo_original_path' => null,
                    'anexo_original_nome' => null,
                    'anexo_original_mime' => null,
                    'anexo_original_tamanho' => null,
                ]);
            }

            $mensagem->update($updates);

            if ($data['modo_resposta'] === 'digitada') {
                $this->timbrado->aplicar($mensagem);
            } else {
                $mensagem->forceFill([
                    'arquivo_final_path' => null,
                    'arquivo_final_nome' => null,
                    'arquivo_final_mime' => null,
                    'arquivo_final_tamanho' => null,
                    'timbrado_aplicado_em' => null,
                ])->save();
            }

            $mensagem->carta->update([
                'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
                'atualizada_por' => $request->user()->id,
            ]);

            CartaEvento::create([
                'carta_id' => $mensagem->carta_id,
                'carta_mensagem_id' => $mensagem->id,
                'user_id' => $request->user()->id,
                'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
                'dados_antes' => $before,
                'dados_depois' => [
                    'mensagem_status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
                    'carta_status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
                ],
            ]);
        });

        return redirect()
            ->route('cartas.cartas.show', $mensagem->carta)
            ->with('status', 'Ajuste enviado para verificação.');
    }

    public function download(Request $request, CartaMensagem $mensagem)
    {
        $mensagem->load('carta.voluntario', 'carta.educando.user', 'carta.educando.municipio');
        $this->authorizeCartaAccess($request, $mensagem->carta);

        $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $filename = $this->gerarNomeArquivo($mensagem, $path);

        return Storage::disk('local')->download($path, $filename);
    }

    public function preview(Request $request, CartaMensagem $mensagem)
    {
        $mensagem->load('carta.voluntario', 'carta.educando.user', 'carta.educando.municipio');
        $this->authorizeCartaAccess($request, $mensagem->carta);

        $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $filename = $this->gerarNomeArquivo($mensagem, $path);
        $mime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime ?: Storage::disk('local')->mimeType($path);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $filename).'"',
        ]);
    }

    public function downloadBatch(Request $request)
    {
        abort_unless($this->isGestor($request->user()), 403);

        $search = trim((string) $request->query('q', ''));
        $municipioId = $request->query('municipio_id');

        $cartas = Carta::query()
            ->with([
                'educando.user', 
                'educando.municipio.estado', 
                'voluntario', 
                'mensagens' => function($q) {
                    $q->orderBy('rodada', 'asc');
                }
            ])
            ->when($search !== '', function ($query) use ($search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $query->where(function ($nested) use ($searchLower) {
                    $nested->whereRaw('LOWER(codigo) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereHas('educando.user', fn ($q) => $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$searchLower}%"]))
                        ->orWhereHas('voluntario', fn ($q) => $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->when($municipioId, function ($query) use ($municipioId) {
                $query->whereHas('educando', function ($q) use ($municipioId) {
                    $q->where('municipio_id', $municipioId);
                });
            })
            ->latest()
            ->get();

        $fpdi = new Fpdi();

        foreach ($cartas as $carta) {
            $tempPath = storage_path('app/temp_carta_' . $carta->id . '_' . uniqid() . '.pdf');
            
            // Generate HTML cover for this single letter
            Pdf::view('cartas.cartas.pdf-batch', ['cartas' => collect([$carta])])
                ->format('A4')
                ->save($tempPath);

            // Import the HTML cover pages
            $pageCount = $fpdi->setSourceFile($tempPath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $tplIdx = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($tplIdx);
                $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $fpdi->useTemplate($tplIdx);
            }
            
            // Clean up temp cover
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Append attached PDFs for this letter's messages
            foreach ($carta->mensagens as $mensagem) {
                $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
                $mime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime;
                
                if ($path && $mime === 'application/pdf' && Storage::disk('local')->exists($path)) {
                    $pdfPath = Storage::disk('local')->path($path);
                    try {
                        $pages = $fpdi->setSourceFile($pdfPath);
                        for ($i = 1; $i <= $pages; $i++) {
                            $tplIdx = $fpdi->importPage($i);
                            $size = $fpdi->getTemplateSize($tplIdx);
                            $fpdi->AddPage($size['orientation'], [$size['width'], $size['height']]);
                            $fpdi->useTemplate($tplIdx);
                        }
                    } catch (\Exception $e) {
                        // Ignore corrupt or incompatible PDFs
                    }
                }
            }
        }

        // If no cartas, just output a blank generic cover
        if ($cartas->isEmpty()) {
            $tempPath = storage_path('app/temp_carta_empty_' . uniqid() . '.pdf');
            Pdf::view('cartas.cartas.pdf-batch', ['cartas' => collect()])->format('A4')->save($tempPath);
            $fpdi->setSourceFile($tempPath);
            $fpdi->AddPage();
            $fpdi->useTemplate($fpdi->importPage(1));
            unlink($tempPath);
        }

        $output = $fpdi->Output('S');

        return response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="Cartas-Lote.pdf"',
        ]);
    }

    private function gestorDashboard(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $municipioId = $request->query('municipio_id');

        $cartas = Carta::query()
            ->with(['educando.user', 'educando.municipio.estado', 'voluntario.participante.municipio.estado', 'ultimaMensagem'])
            ->when($search !== '', function ($query) use ($search) {
                $searchLower = mb_strtolower($search, 'UTF-8');
                $query->where(function ($nested) use ($searchLower) {
                    $nested->whereRaw('LOWER(codigo) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereHas('educando.user', fn ($q) => $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$searchLower}%"]))
                        ->orWhereHas('voluntario', fn ($q) => $q->whereRaw('LOWER(users.name) LIKE ?', ["%{$searchLower}%"]));
                });
            })
            ->when($municipioId, function ($query) use ($municipioId) {
                $query->whereHas('educando', function ($q) use ($municipioId) {
                    $q->where('municipio_id', $municipioId);
                });
            })
            ->latest('updated_at')
            ->paginate(9)
            ->withQueryString();

        $engajaUsers = $this->remetenteCandidatosQuery()->get();
        $municipios = \App\Models\Municipio::orderBy('nome')->get();

        return view('cartas.gestor.index', compact('cartas', 'engajaUsers', 'search', 'municipioId', 'municipios'));
    }

    private function voluntarioDashboard(Request $request): View
    {
        $user = $request->user();

        $cartas = Carta::query()
            ->with(['educando.user', 'educando.municipio.estado', 'voluntario.participante.municipio.estado', 'mensagens' => fn ($q) => $q->latest('rodada'), 'ultimaMensagem'])
            ->withCount(['mensagens as mensagens_nao_lidas_count' => function ($query) use ($user) {
                $query->where('destinatario_user_id', $user->id)
                    ->whereNull('lida_em');
            }])
            ->doVoluntario($user)
            ->latest()
            ->get();

        $destinatarios = $this->engajaUsersQuery()->limit(80)->get();

        return view('cartas.voluntario.index', compact('cartas', 'destinatarios'));
    }

    private function engajaUsersQuery()
    {
        return User::query()
            ->where('sistema_origem', User::SISTEMA_ENGAJA)
            ->whereHas('participante.eventos', fn ($q) => $q->where('is_cartas', true))
            ->with('participante.municipio.estado')
            ->orderBy('name');
    }

    private function remetenteCandidatosQuery()
    {
        return User::query()
            ->with('participante.municipio.estado')
            ->orderBy('name');
    }

    private function voluntariosQuery()
    {
        return User::query()
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->role('cartas_voluntario')
            ->with('participante.municipio.estado')
            ->orderBy('name');
    }

    private function selectVoluntario(): ?User
    {
        return $this->voluntariosQuery()
            ->withCount(['cartasComoVoluntario as cartas_abertas_count' => function ($query) {
                $query->whereNotIn('status', [Carta::STATUS_ENCERRADA, Carta::STATUS_RESPONDIDA]);
            }])
            ->orderBy('cartas_abertas_count')
            ->inRandomOrder()
            ->first();
    }

    private function nextCodigo(): string
    {
        $next = ((int) Carta::query()->withTrashed()->max('id')) + 1;

        return str_pad((string) $next, 3, '0', STR_PAD_LEFT);
    }

    private function isGestor(User $user): bool
    {
        return $user->hasAnyRole(['cartas_admin', 'cartas_gestao'])
            || $user->can('cartas.criar')
            || $user->can('cartas.verificar');
    }

    private function authorizeCartaAccess(Request $request, Carta $carta): void
    {
        $user = $request->user();

        if ($this->isGestor($user) || $carta->voluntario_user_id === $user->id) {
            return;
        }

        abort(403);
    }

    private function markConversationAsRead(User $user, Carta $carta): void
    {
        $carta->mensagens()
            ->where('destinatario_user_id', $user->id)
            ->whereNull('lida_em')
            ->update(['lida_em' => now()]);
    }

    private function gerarNomeArquivo(CartaMensagem $mensagem, string $path): string
    {
        $carta = $mensagem->carta;
        $voluntario = $carta->voluntario;

        $educandoParticipante = $carta->educando;
        $educandoUser = $educandoParticipante?->user;

        $cidade = $educandoParticipante?->municipio?->nome ?? 'Cidade';

        if ($mensagem->tipo_remetente === CartaMensagem::TIPO_REMETENTE_VOLUNTARIO) {
            $remetenteNome = $voluntario?->name ?? 'Voluntario';
            $destinatarioNome = $educandoUser?->name ?? 'Educando';
        } else {
            $remetenteNome = $educandoUser?->name ?? 'Educando';
            $destinatarioNome = $voluntario?->name ?? 'Voluntario';
        }

        $sanitize = function ($string) {
            $string = Str::ascii((string) $string);
            $string = preg_replace('/[^a-zA-Z0-9]/', '_', $string);

            return trim((string) preg_replace('/_+/', '_', $string), '_');
        };

        $cidadeSanitized = $sanitize($cidade);
        $remetenteSanitized = $sanitize($remetenteNome);
        $destinatarioSanitized = $sanitize($destinatarioNome);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (! $extension) {
            $originalName = $mensagem->arquivo_final_nome ?: $mensagem->anexo_original_nome;
            $extension = pathinfo((string) $originalName, PATHINFO_EXTENSION) ?: 'pdf';
        }

        return "PAEB_{$cidadeSanitized}_DE_{$remetenteSanitized}_PARA_{$destinatarioSanitized}.{$extension}";
    }
}
