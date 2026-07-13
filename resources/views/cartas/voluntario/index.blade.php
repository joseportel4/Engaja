@extends('cartas.layouts.app')

@section('title', 'Suas cartas - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-volunteer">
        @include('cartas.shared._logo')

        <section class="cpe-volunteer__content">
            <h1>Suas cartas</h1>

            @if ($errors->any())
                <div class="cpe-alert cpe-alert--error">{{ $errors->first() }}</div>
            @endif

            @if (session('status'))
                <div class="cpe-alert">{{ session('status') }}</div>
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
                            <th></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cartas as $carta)
                            @php
                                $primeira = $carta->mensagens->sortBy('rodada')->first();
                                $statusLabel = match ($carta->status) {
                                    'aguardando_voluntario' => 'Recebida',
                                    'aguardando_verificacao' => 'Em preparação',
                                    'aguardando_ajuste' => 'Ajuste solicitado',
                                    'respondida' => 'Respondida',
                                    'aguardando_educando' => 'Aguardando educando',
                                    'encerrada' => 'Encerrada',
                                    default => 'Recebida',
                                };
                                $statusClass = match ($carta->status) {
                                    'aguardando_voluntario' => 'cpe-pill--green',
                                    'aguardando_verificacao' => 'cpe-pill--yellow',
                                    'aguardando_ajuste' => 'cpe-pill--blue',
                                    'respondida' => 'cpe-pill--green',
                                    'encerrada' => 'cpe-pill--blue',
                                    default => 'cpe-pill--green',
                                };
                            @endphp
                            <tr>
                                <td><span class="cpe-pill {{ $statusClass }}">{{ $statusLabel }}</span></td>
                                <td>{{ optional($primeira?->created_at ?? $carta->created_at)->format('d/m/Y') }}</td>
                                <td>
                                    <span class="cpe-truncate" title="{{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}">
                                        {{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="cpe-truncate" title="{{ Auth::user()->nome_com_localidade }}">
                                        {{ Auth::user()->nome_com_localidade }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('cartas.cartas.show', $carta) }}" class="cpe-link cpe-conversation-link">
                                        Abrir carta
                                        @if(($carta->mensagens_nao_lidas_count ?? 0) > 0)
                                            <span class="cpe-unread-badge" aria-label="{{ $carta->mensagens_nao_lidas_count }} mensagem não lida">
                                                {{ $carta->mensagens_nao_lidas_count }}
                                            </span>
                                        @endif
                                    </a>
                                </td>
                                <td>
                                    @if($carta->podeVoluntarioEnviar())
                                        <button type="button" class="cpe-link" data-modal-open="respondCarta-{{ $carta->id }}">Responder</button>
                                    @else
                                        <span class="cpe-link cpe-link--disabled" title="{{ 'Você poderá responder quando uma nova carta for recebida.' }}">Respondido</span>
                                    @endif
                                </td>
                                <td>
                                    @if($primeira?->anexo_original_path)
                                        <button type="button" class="cpe-link" data-print-src="{{ route('cartas.mensagens.preview', $primeira) }}">Imprimir</button>
                                    @else
                                        <button type="button" class="cpe-link">Imprimir</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <div class="cpe-volunteer-empty">
                                        <strong>Você ainda não recebeu nenhuma carta.</strong>
                                        <span>Assim que alguém enviar uma carta para você, enviaremos uma notificação para seu e-mail cadastrado.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <button type="button" class="cpe-button cpe-volunteer__send" data-modal-open="sendCartaModal">Enviar uma carta</button>
        </section>

        @include('cartas.shared._user-menu')

        @foreach($cartas as $carta)
            @php($primeira = $carta->mensagens->sortBy('rodada')->first())
            <div class="cpe-modal" id="openCarta-{{ $carta->id }}">
                <div class="cpe-modal__backdrop"></div>
                <div class="cpe-modal__dialog cpe-modal__dialog--wide">
                    <h2>Carta enviada por {{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}</h2>
                    <p>{{ optional($primeira?->created_at)->format('d/m/Y H:i') }}</p>
                    @if($primeira?->anexo_original_path)
                        @php($primeiraMime = $primeira->arquivo_final_mime ?: $primeira->anexo_original_mime)
                        <div class="cpe-letter-preview cpe-letter-preview--media">
                            @if(str_starts_with((string) $primeiraMime, 'image/'))
                                <img src="{{ route('cartas.mensagens.preview', $primeira) }}" alt="Carta enviada por {{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}">
                            @elseif($primeiraMime === 'application/pdf')
                                <iframe src="{{ route('cartas.mensagens.preview', $primeira) }}#toolbar=0&navpanes=0" title="Carta enviada por {{ $carta->educando?->nome_com_localidade ?? 'Remetente' }}"></iframe>
                            @else
                                <div class="cpe-file-placeholder">Arquivo anexado: {{ $primeira->anexo_original_nome }}</div>
                            @endif
                        </div>
                    @else
                        <div class="cpe-letter-preview">{{ $primeira?->texto ?? 'Carta sem visualização disponível.' }}</div>
                    @endif
                    <div class="cpe-modal-actions cpe-modal-actions--three">
                        <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                        @if($primeira?->anexo_original_path)
                            <button type="button" class="cpe-button cpe-button--ghost" data-print-src="{{ route('cartas.mensagens.preview', $primeira) }}">Imprimir</button>
                        @else
                            <button type="button" class="cpe-button cpe-button--ghost">Imprimir</button>
                        @endif
                        <button type="button" class="cpe-button" data-modal-close data-modal-open="respondCarta-{{ $carta->id }}">Responder</button>
                    </div>
                </div>
            </div>

            <div class="cpe-modal" id="respondCarta-{{ $carta->id }}">
                <div class="cpe-modal__backdrop"></div>
                <div class="cpe-modal__dialog">
                    <h2>Enviar uma carta</h2>
                    <p>Anexe a foto da sua carta.</p>
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
                                <span>Anexar carta manuscrita</span>
                                <input type="radio" name="modo_resposta" value="anexo_manuscrito" required>
                            </label>
                        </div>
                        <div class="cpe-modo-field" data-modo="digitada" hidden>
                            <textarea name="texto" class="cpe-textarea" placeholder="Digite sua carta aqui"></textarea>
                        </div>
                        <div class="cpe-modo-field" data-modo="anexo_manuscrito" hidden>
                            <label class="cpe-upload cpe-upload--compact">
                                <input type="file" name="arquivo" accept=".pdf,image/*">
                                <span>
                                    <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                                    <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
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
        @endforeach

        <div class="cpe-modal" id="sendCartaModal">
            <div class="cpe-modal__backdrop"></div>
            <div class="cpe-modal__dialog">
                <h2>Enviar uma carta</h2>
                <p>Anexe a foto da sua carta.</p>
                <form method="POST" action="{{ route('cartas.voluntario.cartas.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="cpe-upload">
                        <input type="file" name="arquivo" required accept=".pdf,image/*">
                        <span>
                            <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                            <span class="cpe-upload__hint">SVG, PNG, JPG ou GIF (max. 10MB)</span>
                        </span>
                    </label>
                    <label style="font-size:12px;font-weight:600;margin-top:14px;display:block;">Selecione um destinatário</label>
                    <select name="destinatario_user_id" class="cpe-select" required>
                        <option value="">Destinatário</option>
                        @foreach($destinatarios as $destinatario)
                            <option value="{{ $destinatario->id }}">{{ $destinatario->nome_com_localidade }}</option>
                        @endforeach
                    </select>
                    <div class="cpe-modal-actions">
                        <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Fechar</button>
                        <button type="submit" class="cpe-button">Enviar</button>
                    </div>
                </form>
            </div>
        </div>

        @if(session('cartas_thanks'))
            <div class="cpe-modal is-open" id="thanksModal">
                <div class="cpe-modal__backdrop"></div>
                <div class="cpe-modal__dialog">
                    <div class="cpe-thanks-brand">
                        <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
                    </div>
                    <h2>Muito obrigado.</h2>
                    <p>Cada carta enviada é mais do que uma correspondência. É um encontro entre pessoas, histórias e saberes. Obrigado(a) por contribuir para esta rede de diálogo, respeito e esperança construída pelo Projeto ALFA-EJA Brasil.</p>
                    <button type="button" class="cpe-button cpe-button--ghost" style="width:100%;" data-modal-close>Fechar</button>
                </div>
            </div>
        @endif
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-volunteer {
            padding: 0 28px 80px;
        }

        .cpe-volunteer__content {
            width: min(100%, 940px);
            margin: 168px auto 0;
            display: grid;
            gap: 34px;
        }

        .cpe-volunteer .cpe-table {
            table-layout: fixed;
        }

        .cpe-volunteer .cpe-table th:nth-child(1),
        .cpe-volunteer .cpe-table td:nth-child(1) {
            width: 150px;
        }

        .cpe-volunteer .cpe-table th:nth-child(2),
        .cpe-volunteer .cpe-table td:nth-child(2) {
            width: 110px;
        }

        .cpe-volunteer .cpe-table th:nth-child(3),
        .cpe-volunteer .cpe-table td:nth-child(3),
        .cpe-volunteer .cpe-table th:nth-child(4),
        .cpe-volunteer .cpe-table td:nth-child(4) {
            width: 140px;
        }

        .cpe-volunteer .cpe-table th:nth-child(5),
        .cpe-volunteer .cpe-table td:nth-child(5) {
            width: 128px;
        }

        .cpe-volunteer .cpe-table th:nth-child(6),
        .cpe-volunteer .cpe-table td:nth-child(6) {
            width: 96px;
        }

        .cpe-volunteer .cpe-table th:nth-child(7),
        .cpe-volunteer .cpe-table td:nth-child(7) {
            width: 112px;
        }

        .cpe-volunteer .cpe-table td:nth-child(5),
        .cpe-volunteer .cpe-table td:nth-child(6),
        .cpe-volunteer .cpe-table td:nth-child(7) {
            white-space: nowrap;
            overflow: visible;
        }

        .cpe-volunteer .cpe-table td:nth-child(5),
        .cpe-volunteer .cpe-table td:nth-child(6),
        .cpe-volunteer .cpe-table td:nth-child(7) {
            text-align: center;
        }

        .cpe-volunteer .cpe-table th:nth-child(7),
        .cpe-volunteer .cpe-table td:nth-child(7) {
            padding-right: 18px;
        }

        .cpe-conversation-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .cpe-link--disabled {
            color: #999;
            cursor: not-allowed;
        }

        .cpe-unread-badge {
            min-width: 15px;
            height: 15px;
            border-radius: 999px;
            background: #e83b66;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            font-size: 9px;
            font-weight: 800;
            line-height: 1;
        }

        .cpe-truncate {
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .cpe-volunteer h1 {
            margin: 0;
            text-align: center;
            font-size: 28px;
            font-weight: 800;
        }

        .cpe-volunteer__send {
            width: 100%;
            margin-top: 28px;
        }

        .cpe-volunteer-empty {
            min-height: 96px;
            display: grid;
            place-items: center;
            text-align: center;
        }

        .cpe-volunteer-empty strong {
            display: block;
            font-size: 18px;
            color: #111;
        }

        .cpe-volunteer-empty span {
            display: block;
            width: min(100%, 350px);
            color: #666;
            font-size: 13px;
            line-height: 1.2;
        }

        .cpe-modal-actions--three {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .cpe-file-placeholder {
            height: 100%;
            min-height: 330px;
            display: grid;
            place-items: center;
            color: rgba(0, 0, 0, .45);
            font-weight: 700;
        }

        .cpe-upload--compact {
            min-height: 74px;
            margin-top: 10px;
        }

        .cpe-thanks-brand {
            height: 100px;
            border-radius: 7px;
            background: #fff;
            display: grid;
            place-items: center;
            margin-bottom: 24px;
        }

        .cpe-thanks-brand img {
            width: 150px;
        }

        @media (max-width: 720px) {
            .cpe-volunteer__content {
                margin-top: 80px;
            }

            .cpe-modal-actions--three {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
