@extends('cartas.layouts.app')

@section('title', 'Cartas entre pessoas - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-conversation">
        @include('cartas.shared._logo')

        <section class="cpe-conversation__main">
            <div class="cpe-conversation__content">
                @php
                    $remetenteNome = $carta->educando?->nome ?? 'Remetente';
                    $voluntarioNome = $carta->voluntario?->nome ?? 'Voluntário';
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
                                <th>Município do Remetente</th>
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
                                        <span class="cpe-truncate" title="{{ $mensagem->remetenteUsuario?->nome ?? $mensagem->remetenteParticipante?->nome ?? 'Remetente' }}">
                                            {{ $mensagem->remetenteUsuario?->nome
                                                ?? $mensagem->remetenteParticipante?->nome
                                                ?? 'Remetente' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cpe-truncate" title="{{ $mensagem->remetenteUsuario?->municipio_estado ?? $mensagem->remetenteParticipante?->municipio_estado ?? 'Não informado' }}">
                                            {{ $mensagem->remetenteUsuario?->municipio_estado
                                                ?? $mensagem->remetenteParticipante?->municipio_estado
                                                ?? 'Não informado' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="cpe-truncate" title="{{ $mensagem->destinatarioUsuario?->nome ?? $mensagem->destinatarioParticipante?->nome ?? 'Destinatário' }}">
                                            {{ $mensagem->destinatarioUsuario?->nome
                                                ?? $mensagem->destinatarioParticipante?->nome
                                                ?? 'Destinatário' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px; align-items: center;">
                                            <button class="cpe-icon-button" type="button" data-aside-open="aside-mensagem-{{ $mensagem->id }}" aria-label="Abrir mensagem">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                            @if($gestor)
                                                <a href="{{ route('cartas.mensagens.download', $mensagem) }}" class="cpe-icon-button" aria-label="Baixar anexo" download>
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                        <line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
                            {{ $carta->ultimaMensagem?->status === 'ajuste_solicitado'
                                ? 'Uma solicitação de ajuste foi recebida. Abra a carta para verificar e realizar o ajuste.'
                                : 'Você poderá responder quando uma nova carta for recebida.' }}
                        </p>
                    @endif
                @endif
                <a href="{{ route('cartas.dashboard') }}" class="cpe-button cpe-button--ghost cpe-conversation__wide-button">Voltar</a>
            </div>
        </section>

        <aside class="cpe-conversation__aside">
            <div class="cpe-aside-panel cpe-aside-panel--default" id="asideDefault"></div>

            @foreach($carta->mensagens as $mensagem)
                @php($mensagemMime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime)
                <div class="cpe-aside-panel" id="aside-mensagem-{{ $mensagem->id }}" hidden>
                    <h2>Carta enviada por {{ $mensagem->remetenteUsuario?->nome ?? $mensagem->remetenteParticipante?->nome ?? 'Remetente' }}</h2>
                    <p class="cpe-aside-date">{{ optional($mensagem->enviada_em ?? $mensagem->created_at)->format('d/m/Y H:i') }}</p>

                    @if($mensagem->anexo_original_path || $mensagem->arquivo_final_path)
                        <div class="cpe-letter-preview cpe-letter-preview--media cpe-aside-preview">
                            @if(str_starts_with((string) $mensagemMime, 'image/'))
                                <img src="{{ route('cartas.mensagens.preview', $mensagem) }}" alt="Carta enviada">
                            @elseif($mensagemMime === 'application/pdf')
                                <iframe src="{{ route('cartas.mensagens.preview', $mensagem) }}#toolbar=0&navpanes=0" title="Carta enviada"></iframe>
                            @else
                                <div class="cpe-file-placeholder">Arquivo anexado: {{ $mensagem->arquivo_final_nome ?: $mensagem->anexo_original_nome }}</div>
                            @endif
                        </div>
                    @else
                        <div class="cpe-letter-preview cpe-aside-preview">{{ $mensagem->texto ?? 'Carta sem visualização disponível.' }}</div>
                    @endif

                    @if(! ($gestor && $mensagem->status === 'aguardando_verificacao'))
                        <div class="cpe-modal-actions">
                            <button type="button" class="cpe-button cpe-button--ghost" data-aside-close>Fechar</button>
                            @if($mensagem->anexo_original_path || $mensagem->arquivo_final_path)
                                <button type="button" class="cpe-button cpe-button--ghost" data-print-src="{{ route('cartas.mensagens.preview', $mensagem) }}">Imprimir</button>
                            @else
                                <button type="button" class="cpe-button cpe-button--ghost">Imprimir</button>
                            @endif
                        </div>
                    @endif

                    @if($gestor && $mensagem->status === 'aguardando_verificacao')
                        <div class="cpe-verification-box">
                            <form method="POST" class="cpe-adjustment-form">
                                @csrf
                                <textarea name="parecer_verificacao" class="cpe-textarea" placeholder="Informe o ajuste solicitado ao voluntário, caso seja necessário."></textarea>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px;">
                                    <button type="button" class="cpe-button cpe-button--ghost" data-aside-close>Fechar</button>
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
                                <button type="button" class="cpe-button" data-aside-open="aside-adjustMensagem-{{ $mensagem->id }}">
                                    Realizar ajuste
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            @if(! $gestor)
                @foreach($carta->mensagens as $mensagem)
                    @if($mensagem->status === 'ajuste_solicitado' && $mensagem->isEditavelPor(Auth::user()))
                        <div class="cpe-aside-panel" id="aside-adjustMensagem-{{ $mensagem->id }}" hidden>
                            <h2>Realizar ajuste</h2>
                            <p class="cpe-aside-date">Atualize sua resposta conforme a solicitação recebida.</p>

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
                                    <button type="button" class="cpe-button cpe-button--ghost" data-aside-close>Fechar</button>
                                    <button type="submit" class="cpe-button">Enviar ajuste</button>
                                </div>
                            </form>
                        </div>
                    @endif
                @endforeach
            @endif
        </aside>

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
                            <strong>{{ $carta->educando?->nome ?? 'Remetente' }}</strong>
                        </div>
                        <div>
                            <span>Destinatário</span>
                            <strong>{{ $carta->voluntario?->nome ?? 'Voluntário' }}</strong>
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
                        <textarea name="texto" class="cpe-textarea" placeholder="Escreva aqui sua resposta de forma respeitosa e acolhedora."></textarea>
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

        .cpe-conversation .cpe-table {
            table-layout: fixed;
            width: 100%;
        }

        .cpe-conversation .cpe-table th,
        .cpe-conversation .cpe-table td {
            color: #333;
        }

        .cpe-conversation .cpe-table th:nth-child(1),
        .cpe-conversation .cpe-table td:nth-child(1) {
            width: 125px;
        }

        .cpe-conversation .cpe-table th:nth-child(2),
        .cpe-conversation .cpe-table td:nth-child(2) {
            width: 95px;
        }

        .cpe-conversation .cpe-table th:nth-child(3),
        .cpe-conversation .cpe-table td:nth-child(3) {
            width: 130px;
        }

        .cpe-conversation .cpe-table th:nth-child(4),
        .cpe-conversation .cpe-table td:nth-child(4) {
            width: 155px;
        }

        .cpe-conversation .cpe-table th:nth-child(5),
        .cpe-conversation .cpe-table td:nth-child(5) {
            width: 130px;
        }

        .cpe-conversation .cpe-table th:nth-child(6),
        .cpe-conversation .cpe-table td:nth-child(6) {
            width: 75px;
            text-align: center;
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
            min-height: calc(100vh - 120px);
            border-left: 1px solid var(--cpe-line);
        }

        .cpe-aside-panel[hidden] {
            display: none !important;
        }

        .cpe-aside-panel--default {
            min-height: 100%;
            width: 100%;
        }

        .cpe-aside-panel:not(.cpe-aside-panel--default) {
            background: #fff;
            min-height: 100%;
            padding: 36px 32px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .cpe-aside-panel:not(.cpe-aside-panel--default) h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
        }

        .cpe-aside-date {
            margin: -8px 0 8px;
            color: #333;
            font-size: 14px;
        }

        .cpe-aside-preview {
            max-height: calc(100vh - 280px);
            min-height: 240px;
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
            color: #333;
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

        .cpe-adjustment-form .cpe-textarea,
        .cpe-aside-panel .cpe-textarea {
            min-height: 110px;
            color: #222;
        }

        .cpe-adjustment-form .cpe-textarea::placeholder,
        .cpe-aside-panel .cpe-textarea::placeholder {
            color: #333;
            opacity: 1;
        }

        .cpe-current-file {
            color: #333;
            font-size: 12px;
            font-weight: 700;
            margin: 10px 0 0;
        }

        .cpe-verification-note {
            border: 1px solid #dfd8d3;
            border-radius: 8px;
            background: #faf8f6;
            padding: 16px;
            display: grid;
            gap: 10px;
            margin: 14px 0;
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

            .cpe-conversation__aside:has(#asideDefault:not([hidden])) {
                display: none;
            }

            .cpe-conversation__aside:has(#asideDefault[hidden]) {
                display: block;
                position: fixed;
                inset: 0;
                z-index: 1100;
                overflow-y: auto;
                background: #fff;
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
