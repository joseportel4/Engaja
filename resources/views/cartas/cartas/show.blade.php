@extends('cartas.layouts.app')

@section('title', 'Cartas entre pessoas - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-conversation">
        @include('cartas.shared._logo')

        <section class="cpe-conversation__main">
            <div class="cpe-conversation__content">
                @php
                    $remetenteNome = $carta->educando?->nome_com_localidade ?? 'Remetente';
                    $voluntarioNome = $carta->voluntario?->nome_com_localidade ?? 'Voluntário';
                    $remetentePrimeiroNome = str($remetenteNome)->trim()->before(' ')->toString();
                    $voluntarioPrimeiroNome = str($voluntarioNome)->trim()->before(' ')->toString();
                @endphp

                <h1 class="cpe-title">
                    Cartas entre {{ $remetentePrimeiroNome }} e {{ $voluntarioPrimeiroNome }}
                </h1>

                @if (session('status'))
                    <div class="cpe-alert">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="cpe-alert cpe-alert--error">{{ $errors->first() }}</div>
                @endif

                <div class="cpe-table-card">
                    <table class="cpe-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Data</th>
                                <th>Remetente</th>
                                <th>Destinatário</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $respostasExibidas = 0; ?>
                            @foreach($carta->mensagens as $mensagem)
                                <?php
                                    $statusClass = match ($mensagem->status) {
                                        'aprovada' => 'cpe-pill--green',
                                        'aguardando_verificacao' => 'cpe-pill--yellow',
                                        'ajuste_solicitado' => 'cpe-pill--blue',
                                        default => 'cpe-pill--blue',
                                    };

                            if ($mensagem->status === 'aprovada' && ! $loop->first) {
                                $respostasExibidas++;
                            }

                                    $isVoluntario = ! $gestor && $mensagem->tipo_remetente === 'educando';
                                    $statusLabel = match ($mensagem->status) {
                                        'aprovada' => $loop->first
                                            ? ($isVoluntario ? 'Recebida' : 'Enviada')
                                            : ($respostasExibidas === 1 ? 'Respondida' : "Respondida {$respostasExibidas}x"),
                                        'aguardando_verificacao' => 'Em preparação',
                                        'ajuste_solicitado' => 'Ajuste solicitado',
                                        default => 'Enviada',
                                    };
                                ?>
                                <tr>
                                    <td>
                                        <span class="cpe-pill {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>{{ optional($mensagem->enviada_em ?? $mensagem->created_at)->format('d/m/Y') }}</td>
                                    <td>
                                        {{ $mensagem->remetenteUsuario?->nome_com_localidade
                                            ?? $mensagem->remetenteParticipante?->nome_com_localidade
                                            ?? 'Remetente' }}
                                    </td>
                                    <td>
                                        {{ $mensagem->destinatarioUsuario?->nome_com_localidade
                                            ?? $mensagem->destinatarioParticipante?->nome_com_localidade
                                            ?? 'Destinatário' }}
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 16px; align-items: center;">
                                            <button class="cpe-link" type="button" data-modal-open="mensagem-{{ $mensagem->id }}">Abrir</button>
                                            @if($gestor)
                                                <a href="{{ route('cartas.mensagens.download', $mensagem) }}" class="cpe-link" download>Baixar</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                @if(false)
                                    <div class="cpe-modal" id="mensagem-{{ $mensagem->id }}">
                                        <div class="cpe-modal__backdrop"></div>
                                        <div class="cpe-modal__dialog cpe-modal__dialog--wide">
                                            <h2>Carta enviada por {{ $mensagem->remetenteUsuario?->nome_com_localidade ?? 'Voluntário' }}</h2>
                                            <p>{{ optional($mensagem->enviada_em ?? $mensagem->created_at)->format('d/m/Y H:i') }}</p>
                                            <div class="cpe-letter-preview">{{ $mensagem->texto }}</div>
                                            <div class="cpe-modal-actions">
                                                <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                                                <button type="button" class="cpe-button cpe-button--ghost">Imprimir</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @foreach($carta->mensagens as $mensagem)
                    @php($mensagemMime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime)
                    <div class="cpe-modal" id="mensagem-{{ $mensagem->id }}">
                        <div class="cpe-modal__backdrop"></div>
                        <div class="cpe-modal__dialog cpe-modal__dialog--wide">
                            <h2>Carta enviada por {{ $mensagem->remetenteUsuario?->nome_com_localidade ?? $mensagem->remetenteParticipante?->nome_com_localidade ?? 'Remetente' }}</h2>
                            <p>{{ optional($mensagem->enviada_em ?? $mensagem->created_at)->format('d/m/Y H:i') }}</p>

                            @if($mensagem->anexo_original_path || $mensagem->arquivo_final_path)
                                <div class="cpe-letter-preview cpe-letter-preview--media">
                                    @if(str_starts_with((string) $mensagemMime, 'image/'))
                                        <img src="{{ route('cartas.mensagens.preview', $mensagem) }}" alt="Carta enviada">
                                    @elseif($mensagemMime === 'application/pdf')
                                        <iframe src="{{ route('cartas.mensagens.preview', $mensagem) }}#toolbar=0&navpanes=0" title="Carta enviada"></iframe>
                                    @else
                                        <div class="cpe-file-placeholder">Arquivo anexado: {{ $mensagem->arquivo_final_nome ?: $mensagem->anexo_original_nome }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="cpe-letter-preview">{{ $mensagem->texto ?? 'Carta sem visualização disponível.' }}</div>
                            @endif

                            <div class="cpe-modal-actions">
                                <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                                @if($mensagem->anexo_original_path || $mensagem->arquivo_final_path)
                                    <button type="button" class="cpe-button cpe-button--ghost" data-print-src="{{ route('cartas.mensagens.preview', $mensagem) }}">Imprimir</button>
                                @else
                                    <button type="button" class="cpe-button cpe-button--ghost">Imprimir</button>
                                @endif
                            </div>

                            @if($gestor && $mensagem->status === 'aguardando_verificacao')
                                <div class="cpe-verification-box">
                                    <form method="POST" class="cpe-adjustment-form">
                                        @csrf
                                        <textarea name="parecer_verificacao" class="cpe-textarea" placeholder="Informe o ajuste solicitado ao voluntário, caso seja necessário."></textarea>
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                            <button type="submit" formaction="{{ route('cartas.mensagens.adjustment', $mensagem) }}" class="cpe-button cpe-button--ghost">Solicitar ajuste</button>
                                            <button type="submit" formaction="{{ route('cartas.mensagens.approve', $mensagem) }}" class="cpe-button">Aprovar resposta</button>
                                        </div>
                                    </form>
                                </div>
                            @elseif($mensagem->status === 'ajuste_solicitado')
                                <div class="cpe-verification-note">
                                    <strong>Ajuste solicitado:</strong>
                                    <span>{{ $mensagem->parecer_verificacao ?: 'Revise sua resposta e envie novamente para verificação.' }}</span>

                                    @if(! $gestor && $mensagem->isEditavelPor(Auth::user()))
                                        <button type="button" class="cpe-button" data-modal-close data-modal-open="adjustMensagem-{{ $mensagem->id }}">
                                            Realizar ajuste
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if(! $gestor)
                    @foreach($carta->mensagens as $mensagem)
                        @if($mensagem->status === 'ajuste_solicitado' && $mensagem->isEditavelPor(Auth::user()))
                            <div class="cpe-modal" id="adjustMensagem-{{ $mensagem->id }}">
                                <div class="cpe-modal__backdrop"></div>
                                <div class="cpe-modal__dialog">
                                    <h2>Realizar ajuste</h2>
                                    <p>Atualize sua resposta conforme a solicitação recebida.</p>

                                    @if($mensagem->parecer_verificacao)
                                        <div class="cpe-verification-note cpe-verification-note--compact">
                                            <strong>Ajuste solicitado:</strong>
                                            <span>{{ $mensagem->parecer_verificacao }}</span>
                                        </div>
                                    @endif

                                    <form method="POST" action="{{ route('cartas.mensagens.update-adjustment', $mensagem) }}" enctype="multipart/form-data" data-modo-form>
                                        @csrf
                                        @method('PUT')

                                        <div class="cpe-option-grid">
                                            <label class="cpe-choice">
                                                <span class="cpe-choice__icon">T</span>
                                                <span>Digitar uma carta</span>
                                                <input type="radio" name="modo_resposta" value="digitada" @checked($mensagem->canal_entrada === 'digitada') required>
                                            </label>
                                            <label class="cpe-choice">
                                                <span class="cpe-choice__icon" aria-hidden="true">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                                        <path d="M21 12.5l-8.8 8.8a6 6 0 0 1-8.5-8.5l9.5-9.5a4 4 0 0 1 5.7 5.7l-9.6 9.6a2 2 0 1 1-2.8-2.8l8.8-8.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                                <span>Anexar carta em PDF</span>
                                                <input type="radio" name="modo_resposta" value="anexo_manuscrito" @checked($mensagem->canal_entrada === 'anexo_manuscrito') required>
                                            </label>
                                        </div>

                                        <div class="cpe-modo-field" data-modo="digitada" hidden>
                                            <textarea name="texto" class="cpe-textarea" placeholder="Digite sua carta ajustada aqui.">{{ old('texto', $mensagem->texto) }}</textarea>
                                        </div>

                                        <div class="cpe-modo-field" data-modo="anexo_manuscrito" hidden>
                                            <label class="cpe-upload cpe-upload--compact">
                                                <input type="file" name="arquivo" accept=".pdf,application/pdf">
                                                <span>
                                                    <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                                    <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                                                    <span class="cpe-upload__hint">PDF (máx. 10MB)</span>
                                                </span>
                                            </label>

                                            @if($mensagem->anexo_original_nome)
                                                <p class="cpe-current-file">Arquivo atual: {{ $mensagem->anexo_original_nome }}</p>
                                            @endif
                                        </div>

                                        <div class="cpe-modal-actions">
                                            <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                                            <button type="submit" class="cpe-button">Enviar ajuste</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif

                @if($gestor)
                    @if($carta->podeEducandoEnviar())
                        <button type="button" class="cpe-button cpe-conversation__wide-button" data-modal-open="addCartaModal">Adicionar carta</button>
                    @else
                        <p class="cpe-turn-note">
                            {{ $carta->temMensagemPendente()
                                ? 'Há uma resposta aguardando verificação. Resolva-a antes de enviar uma nova carta.'
                                : 'Aguardando a resposta do voluntário para enviar uma nova carta.' }}
                        </p>
                    @endif
                @else
                    @if($carta->podeVoluntarioEnviar())
                        <button type="button" class="cpe-button cpe-conversation__wide-button" data-modal-open="respondCartaModal">Responder {{ $remetentePrimeiroNome }}</button>
                    @else
                        <p class="cpe-turn-note">
                            {{ 'Você poderá responder quando uma nova carta for recebida.' }}
                        </p>
                    @endif
                @endif
                <a href="{{ route('cartas.dashboard') }}" class="cpe-button cpe-button--ghost cpe-conversation__wide-button">Voltar</a>
            </div>
        </section>

        <aside class="cpe-conversation__aside" aria-hidden="true"></aside>

        @include('cartas.shared._user-menu')
    </main>

    @if($gestor)
        <div class="cpe-modal" id="addCartaModal">
            <div class="cpe-modal__backdrop"></div>
            <div class="cpe-modal__dialog">
                <h2>Responder {{ $remetentePrimeiroNome }}</h2>
                <p>Anexe o arquivo PDF da sua carta.</p>
                <form method="POST" action="{{ route('cartas.cartas.mensagens.store', $carta) }}" enctype="multipart/form-data">
                    @csrf
                    <label class="cpe-upload">
                        <input type="file" name="arquivo" required accept=".pdf,application/pdf">
                        <span>
                            <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                            <span class="cpe-upload__hint">PDF (máx. 10MB)</span>
                        </span>
                    </label>

                    <div class="cpe-fixed-participants">
                        <div>
                            <span>Remetente</span>
                            <strong>{{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}</strong>
                        </div>
                        <div>
                            <span>Destinatário</span>
                            <strong>{{ $carta->voluntario?->nome_com_localidade ?? 'Voluntário' }}</strong>
                        </div>
                    </div>

                    <div class="cpe-modal-actions">
                        <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                        <button type="submit" class="cpe-button">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="cpe-modal" id="respondCartaModal">
            <div class="cpe-modal__backdrop"></div>
            <div class="cpe-modal__dialog">
                <h2>Enviar uma carta</h2>
                <p>Escolha como deseja responder.</p>
                <form method="POST" action="{{ route('cartas.cartas.respond', $carta) }}" enctype="multipart/form-data" data-modo-form>
                    @csrf
                    <div class="cpe-option-grid">
                        <label class="cpe-choice">
                            <span class="cpe-choice__icon">T</span>
                            <span>Digitar uma carta</span>
                            <input type="radio" name="modo_resposta" value="digitada" required>
                        </label>
                        <label class="cpe-choice">
                            <span class="cpe-choice__icon" aria-hidden="true">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                    <path d="M21 12.5l-8.8 8.8a6 6 0 0 1-8.5-8.5l9.5-9.5a4 4 0 0 1 5.7 5.7l-9.6 9.6a2 2 0 1 1-2.8-2.8l8.8-8.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <span>Anexar carta em PDF</span>
                            <input type="radio" name="modo_resposta" value="anexo_manuscrito" required>
                        </label>
                    </div>
                    <div class="cpe-modo-field" data-modo="digitada" hidden>
                        <textarea name="texto" class="cpe-textarea" placeholder="Digite sua carta aqui"></textarea>
                    </div>
                    <div class="cpe-modo-field" data-modo="anexo_manuscrito" hidden>
                        <label class="cpe-upload cpe-upload--compact">
                            <input type="file" name="arquivo" accept=".pdf,application/pdf">
                            <span>
                                <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                                <span class="cpe-upload__hint">PDF (máx. 10MB)</span>
                            </span>
                        </label>
                    </div>
                    <div class="cpe-modal-actions">
                        <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                        <button type="submit" class="cpe-button">Enviar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @include('cartas.shared._scripts')

    <style>
        .cpe-conversation {
            display: grid;
            grid-template-columns: minmax(560px, 1.08fr) .92fr;
        }

        .cpe-conversation > .cpe-logo-top {
            grid-column: 1 / -1;
        }

        .cpe-conversation__main {
            min-height: 100%;
            padding: 0 30px;
        }

        .cpe-conversation__content {
            margin-top: 70px;
            padding-bottom: 56px;
            display: grid;
            gap: 22px;
        }

        .cpe-conversation__aside {
            background: var(--cpe-pink-panel);
            min-height: 100%;
        }

        .cpe-conversation__wide-button {
            width: 100%;
        }

        .cpe-turn-note {
            border: 1px solid #d7d0ca;
            border-radius: 6px;
            background: #fff;
            padding: 12px 14px;
            margin: 0;
            color: #666;
            font-size: 13px;
            text-align: center;
        }

        .cpe-form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 18px;
        }

        .cpe-fixed-participants {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }

        .cpe-fixed-participants div {
            min-width: 0;
            border: 1px solid #d6d6d6;
            border-radius: 6px;
            background: #fff;
            padding: 10px 12px;
        }

        .cpe-fixed-participants span {
            display: block;
            color: #777;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .cpe-fixed-participants strong {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            color: #222;
            font-size: 13px;
        }

        .cpe-verification-box {
            border-top: 1px solid #ddd8d4;
            display: grid;
            gap: 12px;
            margin-top: 18px;
            padding-top: 18px;
        }

        .cpe-verification-box .cpe-button,
        .cpe-adjustment-form .cpe-button {
            width: 100%;
        }

        .cpe-adjustment-form {
            display: grid;
            gap: 10px;
        }

        .cpe-adjustment-form .cpe-textarea {
            min-height: 86px;
        }

        .cpe-current-file {
            color: #666;
            font-size: 12px;
            font-weight: 700;
            margin: 10px 0 0;
        }

        .cpe-verification-note {
            border: 1px solid #d7d0ca;
            border-radius: 6px;
            background: #fff;
            display: grid;
            gap: 6px;
            margin-top: 18px;
            padding: 12px;
            color: #555;
            font-size: 13px;
        }

        .cpe-verification-note .cpe-button {
            margin-top: 8px;
            width: 100%;
        }

        .cpe-verification-note--compact {
            margin: 14px 0;
        }

        @media (max-width: 980px) {
            .cpe-conversation {
                grid-template-columns: 1fr;
            }

            .cpe-conversation__aside {
                display: none;
            }

            .cpe-form-row {
                grid-template-columns: 1fr;
            }

            .cpe-fixed-participants {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
