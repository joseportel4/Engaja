@extends('cartas.auth._shell')

@section('title', 'Entrar - Cartas para Esperançar')

@section('auth-content')
    <h1 class="cartas-title">Entrar</h1>

    @if (session('status'))
        <div class="cartas-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.login.store') }}" class="cartas-form">
        @csrf
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password" placeholder="Senha" required>
        </div>

        <label class="cartas-check">
            <input type="checkbox" name="terms" value="1" required>
            <span>Li e estou de acordo com os <button type="button" class="cartas-terms-link" id="cartasTermsOpen">termos de uso</button>.</span>
        </label>

        <button type="submit" class="cartas-button">Entrar</button>
    </form>

    <div class="cartas-links">
        <a href="{{ route('cartas.register') }}" class="cartas-link">Criar conta</a>
        <a href="{{ route('cartas.password.request') }}" class="cartas-link">Esqueci minha senha</a>
    </div>

    <div class="cartas-terms-modal is-hidden" id="cartasTermsModal" role="dialog" aria-modal="true" aria-labelledby="cartasTermsTitle">
        <div class="cartas-terms-modal__backdrop" data-close-terms></div>
        <div class="cartas-terms-modal__dialog">
            <div class="cartas-terms-modal__header">
                <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
                <button type="button" class="cartas-terms-modal__close" data-close-terms aria-label="Fechar termos">×</button>
            </div>

            <h2 id="cartasTermsTitle">Termos de uso</h2>

            <div class="cartas-terms-modal__content">
                <p><strong>1. Ciência sobre acesso aos conteúdos</strong></p>
                <p>Estou ciente de que as cartas, imagens, documentos e demais conteúdos inseridos nesta plataforma serão acessados exclusivamente pelas equipes responsáveis pela gestão da ação, incluindo profissionais autorizados do Projeto ALFA-EJA Brasil, do Instituto Paulo Freire, da Petrobras e da equipe técnica responsável pela administração e suporte da plataforma.</p>
                <p>Esse acesso ocorrerá para fins de organização, distribuição das correspondências, acompanhamento pedagógico, curadoria, monitoramento, documentação e avaliação da ação.</p>

                <p><strong>2. Autorização de uso da obra intelectual</strong></p>
                <p>Autorizo, de forma gratuita e sem exclusividade, a utilização total ou parcial dos conteúdos produzidos por mim no âmbito da ação Cartas para Esperançar, incluindo textos, cartas, desenhos, fotografias, ilustrações, poemas, relatos, depoimentos e demais produções autorais.</p>
                <p>A autorização compreende o uso para fins educativos, comunicacionais, institucionais, científicos, culturais e de memória do projeto, em materiais impressos e digitais.</p>

                <p><strong>3. Responsabilidade sobre o conteúdo enviado</strong></p>
                <p>Declaro que sou responsável pelas informações, imagens e produções que inserir na plataforma e que não incluirei conteúdos ofensivos, discriminatórios ou que violem direitos de terceiros.</p>

                <p><strong>4. Consentimento</strong></p>
                <p>Ao prosseguir, confirmo que li e estou de acordo com estes termos de participação.</p>
            </div>

            <button type="button" class="cartas-terms-modal__button" data-close-terms>Fechar</button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .cartas-terms-link {
            border: 0;
            background: transparent;
            color: #005bd6;
            padding: 0;
            font: inherit;
            font-weight: 700;
            text-decoration: underline;
        }

        .cartas-terms-link:hover,
        .cartas-terms-link:focus {
            color: #a800d6;
            outline: none;
        }

        .cartas-terms-modal {
            position: fixed;
            inset: 0;
            z-index: 2100;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .cartas-terms-modal.is-hidden {
            display: none;
        }

        .cartas-terms-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .62);
            backdrop-filter: blur(6px);
        }

        .cartas-terms-modal__dialog {
            position: relative;
            width: min(100%, 520px);
            max-height: calc(100vh - 48px);
            border-radius: 8px;
            background: #f4f0ec;
            padding: 18px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .32);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .cartas-terms-modal__header {
            min-height: 72px;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .cartas-terms-modal__header img {
            width: 126px;
            height: auto;
        }

        .cartas-terms-modal__close {
            position: absolute;
            right: 12px;
            top: 10px;
            width: 30px;
            height: 30px;
            border: 0;
            border-radius: 50%;
            background: transparent;
            color: #555;
            font-size: 24px;
            line-height: 1;
        }

        .cartas-terms-modal__close:hover,
        .cartas-terms-modal__close:focus {
            background: #f4f0ec;
            color: #a800d6;
            outline: none;
        }

        .cartas-terms-modal h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .cartas-terms-modal__content {
            max-height: 44vh;
            overflow-y: auto;
            padding-right: 8px;
            font-size: 13px;
            line-height: 1.35;
            color: #333;
        }

        .cartas-terms-modal__content p {
            margin: 0 0 12px;
        }

        .cartas-terms-modal__button {
            width: 100%;
            height: 36px;
            border: 1px solid #d0d0d0;
            border-radius: 5px;
            background: #fff;
            color: #333;
            font-size: 13px;
            font-weight: 700;
        }

        .cartas-terms-modal__button:hover,
        .cartas-terms-modal__button:focus {
            border-color: #a800d6;
            color: #8000a5;
            outline: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('cartasTermsModal');
            const open = document.getElementById('cartasTermsOpen');
            const closeButtons = modal?.querySelectorAll('[data-close-terms]') ?? [];

            const closeModal = () => modal?.classList.add('is-hidden');

            open?.addEventListener('click', () => {
                modal?.classList.remove('is-hidden');
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', closeModal);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeModal();
                }
            });
        });
    </script>
@endpush
