<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaEvento;
use App\Models\Cartas\CartaMensagem;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CartaController extends Controller
{
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
            'voluntario',
            'mensagens.remetenteUsuario',
            'mensagens.remetenteParticipante.user',
            'mensagens.destinatarioUsuario',
            'mensagens.destinatarioParticipante.user',
        ]);

        $gestor = $this->isGestor($request->user());
        if (! $gestor) {
            $this->markConversationAsRead($request->user(), $carta);
            $carta->load('mensagens');
        }

        $engajaUsers = $gestor ? $this->engajaUsersQuery()->get() : collect();
        $voluntarios = $gestor ? $this->voluntariosQuery()->get() : collect();

        return view('cartas.operacional.show', compact('carta', 'gestor', 'engajaUsers', 'voluntarios'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $data = $request->validate([
            'remetente_user_id' => [
                'required',
                Rule::exists('users', 'id')->where('sistema_origem', User::SISTEMA_ENGAJA),
            ],
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'remetente_user_id.required' => 'Selecione o remetente.',
            'arquivo.required' => 'Selecione o arquivo da carta.',
            'arquivo.mimes' => 'Envie um arquivo PDF ou imagem.',
        ]);

        $remetente = User::with('participante')->findOrFail($data['remetente_user_id']);
        $participante = $remetente->participante;

        if (! $participante) {
            return back()->withErrors(['remetente_user_id' => 'O remetente selecionado nao possui participante vinculado.'])->withInput();
        }

        $voluntario = $this->selectVoluntario();
        if (! $voluntario) {
            return back()->withErrors(['destinatario' => 'Nao ha voluntarios disponiveis para receber a carta.'])->withInput();
        }

        $file = $request->file('arquivo');

        DB::transaction(function () use ($request, $participante, $voluntario, $file) {
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
        });

        return redirect()->route('cartas.dashboard')->with('status', 'Carta enviada para o voluntario.');
    }

    public function storeMessage(Request $request, Carta $carta): RedirectResponse
    {
        abort_unless($this->isGestor($request->user()), 403);

        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'arquivo.required' => 'Selecione o arquivo da carta.',
            'arquivo.mimes' => 'Envie um arquivo PDF ou imagem.',
        ]);

        $carta->loadMissing(['educando.user', 'voluntario']);
        $participante = $carta->educando;
        $voluntario = $carta->voluntario;

        abort_unless($participante && $voluntario, 422);

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

        return redirect()->route('cartas.cartas.show', $carta)->with('status', 'Carta adicionada.');
    }

    public function respond(Request $request, Carta $carta): RedirectResponse
    {
        abort_unless($carta->voluntario_user_id === $request->user()->id, 403);

        $data = $request->validate([
            'modo_resposta' => ['required', Rule::in(['digitada', 'anexo_manuscrito'])],
            'texto' => ['nullable', 'required_if:modo_resposta,digitada', 'string', 'max:12000'],
            'arquivo' => ['nullable', 'required_if:modo_resposta,anexo_manuscrito', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'modo_resposta.required' => 'Selecione como deseja responder.',
            'texto.required_if' => 'Digite a carta antes de enviar.',
            'arquivo.required_if' => 'Anexe a carta manuscrita antes de enviar.',
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
            'arquivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'destinatario_user_id.required' => 'Selecione o destinatario.',
            'arquivo.required' => 'Selecione o arquivo da carta.',
        ]);

        $destinatario = User::with('participante')->findOrFail($data['destinatario_user_id']);
        if (! $destinatario->participante) {
            return back()->withErrors(['destinatario_user_id' => 'O destinatario selecionado nao possui participante vinculado.'])->withInput();
        }

        $file = $request->file('arquivo');

        DB::transaction(function () use ($request, $user, $destinatario, $file) {
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
            'arquivo' => ['nullable', 'required_if:modo_resposta,anexo_manuscrito', 'file', 'mimes:pdf,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'modo_resposta.required' => 'Selecione como deseja ajustar a resposta.',
            'texto.required_if' => 'Digite a carta ajustada antes de enviar.',
            'arquivo.required_if' => 'Anexe a carta ajustada antes de enviar.',
            'arquivo.mimes' => 'Envie um arquivo PDF ou imagem.',
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
        $mensagem->load('carta');
        $this->authorizeCartaAccess($request, $mensagem->carta);

        $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $mensagem->arquivo_final_nome ?: $mensagem->anexo_original_nome);
    }

    public function preview(Request $request, CartaMensagem $mensagem)
    {
        $mensagem->load('carta');
        $this->authorizeCartaAccess($request, $mensagem->carta);

        $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        $name = $mensagem->arquivo_final_nome ?: $mensagem->anexo_original_nome ?: 'carta';
        $mime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime ?: Storage::disk('local')->mimeType($path);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $name).'"',
        ]);
    }

    private function gestorDashboard(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $cartas = Carta::query()
            ->with(['educando.user', 'voluntario', 'ultimaMensagem'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('codigo', 'like', "%{$search}%")
                        ->orWhereHas('educando.user', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('voluntario', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        $engajaUsers = $this->engajaUsersQuery()->get();

        return view('cartas.operacional.gestor', compact('cartas', 'engajaUsers', 'search'));
    }

    private function voluntarioDashboard(Request $request): View
    {
        $user = $request->user();

        $cartas = Carta::query()
            ->with(['educando.user', 'mensagens' => fn ($q) => $q->latest('rodada')])
            ->withCount(['mensagens as mensagens_nao_lidas_count' => function ($query) use ($user) {
                $query->where('destinatario_user_id', $user->id)
                    ->whereNull('lida_em');
            }])
            ->doVoluntario($user)
            ->latest()
            ->get();

        $destinatarios = $this->engajaUsersQuery()->limit(80)->get();

        return view('cartas.operacional.voluntario', compact('cartas', 'destinatarios'));
    }

    private function engajaUsersQuery()
    {
        return User::query()
            ->where('sistema_origem', User::SISTEMA_ENGAJA)
            ->whereHas('participante')
            ->with('participante')
            ->orderBy('name');
    }

    private function voluntariosQuery()
    {
        return User::query()
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->role('cartas_voluntario')
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
}
