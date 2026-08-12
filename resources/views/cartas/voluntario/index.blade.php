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

            @if($cartas->isEmpty())
                <div class="cpe-empty-card">
                    <div class="cpe-empty-frame">
                        <span class="cpe-empty-corner cpe-empty-corner--tl"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--tr"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--bl"></span>
                        <span class="cpe-empty-corner cpe-empty-corner--br"></span>
                        <strong>Você ainda não recebeu nenhuma carta</strong>
                        <span class="cpe-empty-hint">Assim que alguém enviar uma carta para você, enviaremos uma notificação para seu e-mail cadastrado.</span>
                    </div>
                </div>
            @else
                <div class="cpe-stack" data-cpe-stack tabindex="0" role="group" aria-label="Suas cartas — use as setas do teclado para navegar">
                    @foreach($cartas as $carta)
                        @php
                            $primeira = $carta->mensagens->sortBy('rodada')->first();
                            $selo = match ($carta->status) {
                                'aguardando_voluntario' => ['img' => 'selo-recebida.png', 'alt' => 'Carta recebida', 'tipo' => 'selo'],
                                'aguardando_verificacao' => ['img' => 'selo-enviada.png', 'alt' => 'Carta enviada', 'tipo' => 'selo'],
                                'aguardando_ajuste' => ['img' => 'carimbo-ajuste.png', 'alt' => 'Ajuste solicitado', 'tipo' => 'carimbo'],
                                default => ['img' => 'selo-respondida.png', 'alt' => 'Carta respondida', 'tipo' => 'selo'],
                            };
                            $dataCarta = optional($primeira?->created_at ?? $carta->created_at)->format('d/m/Y');
                        @endphp
                        <article class="cpe-envelope">
                            <img class="cpe-envelope__bg" src="{{ asset('images/cartas/envelope.png') }}" alt="" aria-hidden="true">
                            <img class="cpe-envelope__selo cpe-envelope__selo--{{ $selo['tipo'] }}" src="{{ asset('images/cartas/'.$selo['img']) }}" alt="{{ $selo['alt'] }}">
                            <a class="cpe-envelope__open" href="{{ route('cartas.cartas.show', $carta) }}" aria-label="Abrir carta de {{ $carta->educando?->nome ?? 'Remetente' }}">
                                <img src="{{ asset('images/cartas/botao-abrir-carta.png') }}" alt="Abrir carta">
                                @if(($carta->mensagens_nao_lidas_count ?? 0) > 0)
                                    <span class="cpe-unread-badge cpe-envelope__unread" aria-label="{{ $carta->mensagens_nao_lidas_count }} mensagem não lida">{{ $carta->mensagens_nao_lidas_count }}</span>
                                @endif
                            </a>
                            <div class="cpe-envelope__meta">
                                <span class="cpe-envelope__party">De: {{ $carta->educando?->nome ?? 'Remetente' }}<br>Para: {{ Auth::user()->name }}</span>
                                <span class="cpe-envelope__date">{{ $dataCarta }}</span>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        @include('cartas.shared._user-menu')

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
        @include('cartas.shared._help_fab')
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-volunteer {
            padding: 0 28px 56px;
            display: flex;
            flex-direction: column;
            /* Contem o carrossel full-bleed (100vw) na largura real da
               viewport. Precisa ficar aqui, e nao no __content (940px),
               senao as cartas das pontas seriam cortadas pela coluna. */
            overflow-x: clip;
        }

        .cpe-volunteer__content {
            width: min(100%, 940px);
            margin: 120px auto 0;
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 34px;
        }

        .cpe-unread-badge {
            min-width: 18px;
            height: 18px;
            border-radius: 999px;
            background: #e83b66;
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 5px;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
        }

        /* --- Carrossel de envelopes (full-bleed) ---
           Sem scroll nativo: cada envelope e posicionado por transform a
           partir do centro, conforme sua distancia (--cpe-offset) ate o
           item em foco. Isso mantem o ativo sempre no centro exato da tela
           e elimina as condicoes de corrida entre scroll-snap e scrollTo
           que vinham quebrando a navegacao. */
        .cpe-stack {
            /* O deslocamento de cada carta nao e linear (ver POSICOES no JS):
               as pontas sao comprimidas para caberem inteiras na tela. A carta
               mais externa termina em vw/2 + 1.15 * --cpe-card-w, entao manter
               a carta em <=43vw garante que nenhuma das 5 seja cortada — e o
               fade de entrada/saida acontece todo dentro da area visivel.
               O teto de 600px acompanha a arte do envelope (606px), evitando
               ampliar a imagem alem do tamanho nativo. */
            --cpe-card-w: min(43vw, 600px);
            position: relative;
            width: 100vw;
            margin-left: calc(50% - 50vw);
            margin-right: calc(50% - 50vw);
            height: calc(var(--cpe-card-w) * 326 / 606 + 96px);
            overflow: hidden;
            touch-action: pan-y;
        }

        .cpe-stack:focus-visible {
            outline: 2px solid var(--cpe-purple);
            outline-offset: -4px;
        }

        .cpe-envelope {
            position: absolute;
            left: 50%;
            top: 50%;
            width: var(--cpe-card-w);
            aspect-ratio: 606 / 326;
            container-type: inline-size;
            transform: translate(-50%, -50%) translateX(calc(var(--cpe-x, 0) * var(--cpe-card-w))) scale(.5);
            opacity: 0;
            z-index: 1;
            transition: transform .45s cubic-bezier(.22, .61, .36, 1), opacity .45s ease;
            will-change: transform, opacity;
        }

        /* Vizinhos: ficam atras e sao parcialmente cobertos pelo item em foco.
           A profundidade vem so de escala + opacidade (sem blur). */
        .cpe-envelope[data-cpe-dist="1"] {
            transform: translate(-50%, -50%) translateX(calc(var(--cpe-x) * var(--cpe-card-w))) scale(.84);
            opacity: .7;
            z-index: 2;
        }

        .cpe-envelope[data-cpe-dist="2"] {
            transform: translate(-50%, -50%) translateX(calc(var(--cpe-x) * var(--cpe-card-w))) scale(.58);
            opacity: .45;
            z-index: 1;
        }

        /* Item em foco: centro exato da tela, por cima dos vizinhos. */
        .cpe-envelope.is-active {
            transform: translate(-50%, -50%) scale(1);
            opacity: 1;
            z-index: 3;
        }

        /* Fora da janela de 5: fade out deslizando para fora do carrossel. */
        .cpe-envelope[data-cpe-hidden] {
            opacity: 0;
            pointer-events: none;
        }

        .cpe-envelope:not(.is-active) {
            cursor: pointer;
        }

        .cpe-envelope:not(.is-active) .cpe-envelope__open {
            pointer-events: none;
        }

        .cpe-envelope__bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            pointer-events: none;
            user-select: none;
        }

        .cpe-envelope__selo {
            position: absolute;
            top: 28%;
            left: 7%;
            width: 21%;
            transform: rotate(-16.23deg);
            transform-origin: center;
            pointer-events: none;
            user-select: none;
        }

        .cpe-envelope__selo--carimbo {
            top: 1%;
            left: 50%;
            width: 40%;
            transform: translateX(-50%) rotate(-4deg);
        }

        .cpe-envelope__open {
            position: absolute;
            left: 50%;
            top: 52%;
            transform: translate(-50%, -50%);
            width: 13.5%;
            display: block;
            transition: transform .2s ease;
        }

        .cpe-envelope__open img {
            display: block;
            width: 100%;
            height: auto;
        }

        .cpe-envelope__open:hover,
        .cpe-envelope__open:focus-visible {
            transform: translate(-50%, -50%) scale(1.08);
            outline: none;
        }

        .cpe-envelope__unread {
            position: absolute;
            top: -6px;
            right: -6px;
        }

        .cpe-envelope__meta {
            position: absolute;
            left: 8%;
            right: 11%;
            bottom: 13%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
            color: #008BBC;
            font-weight: 500;
            line-height: 1.2;
        }

        .cpe-envelope__party {
            font-size: clamp(11px, 2.64cqi, 16px);
        }

        .cpe-envelope__date {
            font-size: clamp(11px, 2.64cqi, 16px);
            text-align: right;
            white-space: nowrap;
        }

        /* O transform carrega o posicionamento do carrossel, entao aqui so
           removemos a animacao — nunca o transform em si. */
        @media (prefers-reduced-motion: reduce) {
            .cpe-envelope {
                transition: none;
            }
        }

        .cpe-volunteer h1 {
            margin: 0 0 40px;
            text-align: center;
            font-size: 32px;
            font-weight: 600;
            line-height: 1.2;
        }

        .cpe-empty-card {
            width: min(100%, 432px);
            margin: 0 auto;
            background: rgba(150, 2, 199, .05);
            border-radius: 8px;
            padding: 14px;
            display: grid;
            place-items: center;
        }

        .cpe-empty-frame {
            position: relative;
            width: 100%;
            padding: 24px 20px;
            display: grid;
            justify-items: center;
            gap: 8px;
        }

        .cpe-empty-corner {
            position: absolute;
            width: 40px;
            height: 40px;
        }

        .cpe-empty-corner--tl {
            top: 0;
            left: 0;
            border-top: 1px solid var(--cpe-purple);
            border-left: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--tr {
            top: 0;
            right: 0;
            border-top: 1px solid var(--cpe-purple);
            border-right: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--bl {
            bottom: 0;
            left: 0;
            border-bottom: 1px solid var(--cpe-purple);
            border-left: 1px solid var(--cpe-purple);
        }

        .cpe-empty-corner--br {
            bottom: 0;
            right: 0;
            border-bottom: 1px solid var(--cpe-purple);
            border-right: 1px solid var(--cpe-purple);
        }

        .cpe-empty-frame strong {
            display: block;
            width: 100%;
            font-size: 22px;
            font-weight: 700;
            line-height: 1.2;
            text-align: center;
            text-transform: uppercase;
            color: var(--cpe-purple);
        }

        .cpe-empty-hint {
            display: block;
            width: min(100%, 274px);
            color: #414652;
            font-size: 14px;
            line-height: 1.2;
            text-align: center;
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
                margin-top: 60px;
            }

            /* Em telas estreitas nao ha espaco para as 5 cartas inteiras; a
               carta em foco tem prioridade e as extremas sangram na borda. */
            .cpe-stack {
                --cpe-card-w: min(76vw, 320px);
                height: calc(var(--cpe-card-w) * 326 / 606 + 64px);
            }

            .cpe-modal-actions--three {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const stack = document.querySelector('[data-cpe-stack]');
            if (!stack) {
                return;
            }

            const cards = Array.from(stack.querySelectorAll('.cpe-envelope'));
            if (!cards.length) {
                return;
            }

            // Quantos envelopes aparecem de cada lado do que esta em foco:
            // 2 + 1 + 2 = 5 renderizados simultaneamente.
            const LADO = 2;

            // Deslocamento de cada carta ate o centro, em larguras de carta,
            // indexado pela distancia (0 = em foco, 3 = fora da janela).
            // Nao e linear de proposito: comprimir as pontas e o que permite
            // aumentar a carta em foco sem que as extremas saiam da tela.
            const POSICOES = [0, 0.63, 0.86, 1.12];
            const ultimo = cards.length - 1;
            const limitar = (i) => Math.max(0, Math.min(ultimo, i));
            let indice = 0;

            /**
             * Reposiciona todos os envelopes em funcao do indice em foco.
             * O CSS cuida da transicao (fade in/out, escala e deslocamento);
             * aqui so declaramos a distancia de cada carta ate o centro.
             */
            const aplicar = () => {
                cards.forEach((card, i) => {
                    const offset = i - indice;
                    const distancia = Math.abs(offset);
                    const visivel = distancia <= LADO;
                    const ativo = offset === 0;

                    // Cartas fora da janela param na posicao de borda (LADO + 1)
                    // e ficam invisiveis: ao entrar/sair da janela elas deslizam
                    // de la, o que produz o fade in/out nas pontas.
                    const banda = Math.min(distancia, LADO + 1);

                    card.style.setProperty('--cpe-x', POSICOES[banda] * Math.sign(offset));
                    card.dataset.cpeDist = String(banda);
                    card.classList.toggle('is-active', ativo);
                    card.setAttribute('aria-hidden', visivel ? 'false' : 'true');

                    if (visivel) {
                        delete card.dataset.cpeHidden;
                    } else {
                        card.dataset.cpeHidden = '';
                    }

                    // So a carta em foco e acionavel; as demais recebem clique
                    // para virem ao centro (ver handler abaixo).
                    const link = card.querySelector('.cpe-envelope__open');
                    if (link) {
                        link.tabIndex = ativo ? 0 : -1;
                    }
                });
            };

            const irPara = (i) => {
                const alvo = limitar(i);
                if (alvo === indice) {
                    return;
                }
                indice = alvo;
                aplicar();
            };

            // Roda do mouse / trackpad: um passo por "clique" (usa o eixo de
            // maior magnitude — deltaY na roda comum, deltaX em gestos
            // horizontais — so a direcao importa, nunca a magnitude, que varia
            // por dispositivo/navegador).
            const ESPERA = 90; // ms
            let ultimoGiro = 0;
            stack.addEventListener('wheel', (event) => {
                // Sempre cancelado: com o cursor sobre o carrossel a rolagem
                // fica presa a ele, inclusive nos extremos — o gesto nunca
                // vaza para a pagina.
                event.preventDefault();

                const delta = Math.abs(event.deltaX) > Math.abs(event.deltaY) ? event.deltaX : event.deltaY;
                const direcao = Math.sign(delta);
                if (direcao === 0) {
                    return;
                }
                const agora = event.timeStamp || performance.now();
                if (agora - ultimoGiro < ESPERA) {
                    return;
                }
                ultimoGiro = agora;
                irPara(indice + direcao);
            }, { passive: false });

            stack.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowRight') {
                    event.preventDefault();
                    irPara(indice + 1);
                } else if (event.key === 'ArrowLeft') {
                    event.preventDefault();
                    irPara(indice - 1);
                }
            });

            // Clique em um vizinho traz ele para o centro (o link "Abrir carta"
            // so responde na carta em foco, via pointer-events no CSS).
            cards.forEach((card, i) => {
                card.addEventListener('click', (event) => {
                    if (i !== indice) {
                        event.preventDefault();
                        irPara(i);
                    }
                });
            });

            let toqueX = null;
            stack.addEventListener('touchstart', (event) => {
                toqueX = event.touches[0].clientX;
            }, { passive: true });

            stack.addEventListener('touchend', (event) => {
                if (toqueX === null) {
                    return;
                }
                const dx = event.changedTouches[0].clientX - toqueX;
                toqueX = null;
                if (Math.abs(dx) > 40) {
                    irPara(indice + (dx < 0 ? 1 : -1));
                }
            }, { passive: true });

            aplicar();
        });
    </script>
@endsection
