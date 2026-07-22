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
                <div class="cpe-stack" data-cpe-stack>
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

            <button type="button" class="cpe-button cpe-volunteer__send" data-modal-open="sendCartaModal">Enviar uma carta</button>
        </section>

        @include('cartas.shared._user-menu')

        <div class="cpe-modal" id="sendCartaModal">
            <div class="cpe-modal__backdrop"></div>
            <div class="cpe-modal__dialog">
                <h2>Enviar uma carta</h2>
                <p>Anexe o arquivo PDF da sua carta.</p>
                <form method="POST" action="{{ route('cartas.voluntario.cartas.store') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="cpe-upload">
                        <input type="file" name="arquivo" required accept=".pdf,application/pdf">
                        <span>
                            <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                            <span class="cpe-upload__hint">PDF (máx. 10MB)</span>
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
            padding: 0 28px 56px;
            display: flex;
            flex-direction: column;
        }

        .cpe-volunteer__content {
            width: min(100%, 940px);
            margin: 168px auto 0;
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

        /* --- Pilha de envelopes (carrossel vertical) --- */
        .cpe-stack {
            position: relative;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            max-height: min(70vh, 640px);
            overflow-y: auto;
            padding: 48px 12px;
            scroll-snap-type: y proximity;
            overscroll-behavior: contain;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
        }

        .cpe-stack::-webkit-scrollbar {
            display: none;
        }

        .cpe-envelope {
            position: relative;
            width: min(100%, 606px);
            aspect-ratio: 606 / 326;
            flex: none;
            margin-top: -46px;
            border-radius: 10px;
            box-shadow: 12px 12px 54px rgba(0, 0, 0, .12);
            scroll-snap-align: center;
            container-type: inline-size;
            opacity: .5;
            transform: scale(.9);
            filter: blur(1.5px);
            transition: transform .4s cubic-bezier(.22, .61, .36, 1), opacity .4s ease, filter .4s ease;
            will-change: transform, opacity;
        }

        .cpe-envelope:first-child {
            margin-top: 0;
        }

        .cpe-envelope.is-active {
            opacity: 1;
            transform: scale(1);
            filter: blur(0);
            z-index: 2;
        }

        @supports (animation-timeline: view()) {
            .cpe-envelope {
                opacity: 1;
                filter: none;
                transform: none;
                animation: cpe-envelope-focus linear both;
                animation-timeline: view();
                animation-range: cover 0% cover 100%;
            }
        }

        @keyframes cpe-envelope-focus {
            0% {
                opacity: .4;
                transform: scale(.82) translateY(8px);
                filter: blur(2px);
            }
            50% {
                opacity: 1;
                transform: scale(1) translateY(0);
                filter: blur(0);
            }
            100% {
                opacity: .4;
                transform: scale(.82) translateY(-8px);
                filter: blur(2px);
            }
        }

        .cpe-envelope__bg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 9px;
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

        @media (prefers-reduced-motion: reduce) {
            .cpe-envelope,
            .cpe-envelope.is-active {
                opacity: 1;
                transform: none;
                filter: none;
                animation: none;
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

        .cpe-volunteer__send {
            align-self: center;
            width: 318px;
            max-width: 100%;
            height: 51px;
            margin-top: auto;
            border-radius: 12px;
            box-shadow: 0 8px 44px rgba(0, 0, 0, .25);
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
                margin-top: 80px;
            }

            .cpe-stack {
                max-height: min(64vh, 520px);
                padding: 32px 4px;
            }

            .cpe-envelope {
                margin-top: -30px;
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
            if (cards.length < 2) {
                return;
            }

            const suportaSDA = window.CSS && CSS.supports && CSS.supports('animation-timeline', 'view()');
            const movimentoReduzido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Posicao de scroll (layout, ignora transform) que centraliza um envelope.
            const centro = (card) => card.offsetTop - (stack.clientHeight - card.offsetHeight) / 2;
            const limitar = (i) => Math.max(0, Math.min(cards.length - 1, i));

            // Indice inicial: envelope mais proximo do centro atual.
            let indice = cards.reduce((melhor, card, i) => (
                Math.abs(centro(card) - stack.scrollTop) < Math.abs(centro(cards[melhor]) - stack.scrollTop) ? i : melhor
            ), 0);

            const irPara = (i) => {
                indice = limitar(i);
                stack.scrollTo({ top: centro(cards[indice]), behavior: movimentoReduzido ? 'auto' : 'smooth' });
            };

            // Realce por classe para navegadores sem scroll-driven animations.
            if (!suportaSDA) {
                const realcar = () => {
                    let melhor = 0;
                    let menor = Infinity;
                    cards.forEach((card, i) => {
                        const dist = Math.abs(centro(card) - stack.scrollTop);
                        if (dist < menor) {
                            menor = dist;
                            melhor = i;
                        }
                    });
                    cards.forEach((card, i) => card.classList.toggle('is-active', i === melhor));
                };
                let ticking = false;
                stack.addEventListener('scroll', () => {
                    if (ticking) {
                        return;
                    }
                    ticking = true;
                    requestAnimationFrame(() => {
                        realcar();
                        ticking = false;
                    });
                }, { passive: true });
                realcar();
            }

            // Roda do mouse: um passo por "clique" da roda (so a direcao importa, nunca a
            // magnitude do deltaY — que varia por dispositivo/navegador e causava pulos duplos
            // ou nenhum movimento). Uma pequena janela anti-duplicacao junta eventos em rajada
            // do mesmo entalhe, mantendo giros sustentados avancando um envelope por vez.
            const ESPERA = 40; // ms
            let ultimoGiro = 0;
            stack.addEventListener('wheel', (event) => {
                event.preventDefault();
                const direcao = Math.sign(event.deltaY);
                if (direcao === 0) {
                    return;
                }
                const agora = event.timeStamp || performance.now();
                if (agora - ultimoGiro < ESPERA) {
                    return;
                }
                ultimoGiro = agora;
                const alvo = limitar(indice + direcao);
                if (alvo !== indice) {
                    irPara(alvo);
                }
            }, { passive: false });

            irPara(indice);
            window.addEventListener('resize', () => irPara(indice));
        });
    </script>
@endsection
