@extends('cartas.auth._shell')

@section('title', 'Criar conta - Cartas para Esperançar')

@section('auth-content')
    <h1 class="cartas-title">Crie sua conta</h1>

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('cartas.register.store') }}" class="cartas-form" data-estados-url="{{ route('cartas.localidades.estados') }}" data-municipios-url="{{ route('cartas.localidades.municipios', ['estadoIbgeId' => '__ESTADO__']) }}">
        @csrf
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="text" name="name" value="{{ old('name') }}" placeholder="Digite seu nome completo, sem abreviação." required autofocus>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="Digite seu e-mail." required>
        </div>
        <div class="cartas-field-wrap">
            <input id="cpf" class="cartas-field" type="text" name="cpf" value="{{ old('cpf') }}" inputmode="numeric" maxlength="14" placeholder="Digite seu CPF." required>
        </div>
        <div class="cartas-field-wrap">
            <input id="telefone" class="cartas-field" type="text" name="telefone" value="{{ old('telefone') }}" inputmode="numeric" maxlength="15" placeholder="Digite seu telefone." required>
        </div>
        <div class="cartas-field-wrap">
            <select id="estado_ibge_id" class="cartas-field" name="estado_ibge_id" required>
                <option value="">Selecione seu estado</option>
            </select>
        </div>
        <div id="municipio-wrap" class="cartas-field-wrap" hidden>
            <select id="municipio_ibge_id" class="cartas-field" name="municipio_ibge_id" disabled required>
                <option value="">Selecione seu município</option>
            </select>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password" placeholder="Senha" required>
        </div>

        <div class="cartas-terms-check">
            <label class="cartas-terms-check__label">
                <input type="checkbox" name="termos_aceitos" id="termosCheckbox" value="1" {{ old('termos_aceitos') ? 'checked' : '' }}>
                <span>Li e estou de acordo com os <a href="#" id="openTermsModal" class="cartas-terms-check__link">termos de uso</a>.</span>
            </label>
        </div>

        <button type="submit" class="cartas-button" id="submitBtn" {{ old('termos_aceitos') ? '' : 'disabled' }}>Continuar</button>
    </form>

    <a href="{{ route('cartas.login') }}" class="cartas-link" style="margin-top:28px;font-weight:700;">Já tenho uma conta</a>

    <script>
        const onlyDigits = value => (value || '').replace(/\D+/g, '');
        const maskCpf = value => {
            const digits = onlyDigits(value).slice(0, 11);
            return digits.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        };
        const maskPhone = value => {
            const digits = onlyDigits(value).slice(0, 11);
            if (digits.length <= 10) return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{4})(\d)/, '$1-$2');
            return digits.replace(/(\d{2})(\d)/, '($1) $2').replace(/(\d{5})(\d)/, '$1-$2');
        };

        const cpf = document.getElementById('cpf');
        const telefone = document.getElementById('telefone');
        cpf?.addEventListener('input', event => event.target.value = maskCpf(event.target.value));
        telefone?.addEventListener('input', event => event.target.value = maskPhone(event.target.value));
        if (cpf) cpf.value = maskCpf(cpf.value);
        if (telefone) telefone.value = maskPhone(telefone.value);

        const form = document.querySelector('.cartas-form');
        const estado = document.getElementById('estado_ibge_id');
        const municipio = document.getElementById('municipio_ibge_id');
        const municipioWrap = document.getElementById('municipio-wrap');
        const submitButton = form.querySelector('button[type="submit"]');
        const estadoSelecionado = @json(old('estado_ibge_id'));
        const municipioSelecionado = @json(old('municipio_ibge_id'));
        let municipiosRequestId = 0;

        const addOption = (select, value, label) => select.add(new Option(label, value));
        const setSubmitLoading = loading => {
            submitButton.disabled = loading;
            submitButton.setAttribute('aria-busy', loading ? 'true' : 'false');
        };
        const limparMunicipios = (label = 'Selecione seu município', mostrar = false) => {
            municipio.dataset.loading = 'false';
            municipio.replaceChildren(new Option(label, ''));
            municipio.disabled = true;
            municipioWrap.hidden = !mostrar;
            setSubmitLoading(false);
        };
        const carregarMunicipiosLoading = () => {
            municipio.dataset.loading = 'true';
            municipio.replaceChildren(new Option('Carregando municípios...', ''));
            municipio.disabled = true;
            municipioWrap.hidden = false;
            setSubmitLoading(true);
        };
        const carregarMunicipios = async (estadoId, selecionado = null) => {
            const requestId = ++municipiosRequestId;
            limparMunicipios();
            if (!estadoId) return;
            carregarMunicipiosLoading();
            try {
                const url = form.dataset.municipiosUrl.replace('__ESTADO__', estadoId);
                const resposta = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!resposta.ok) throw new Error();
                const municipios = await resposta.json();
                if (requestId !== municipiosRequestId) return;

                municipio.dataset.loading = 'false';
                municipio.replaceChildren(new Option('Selecione seu município', ''));
                municipios.forEach(item => addOption(municipio, item.id, item.nome));
                municipio.disabled = false;
                municipioWrap.hidden = false;
                setSubmitLoading(false);
                if (selecionado) municipio.value = String(selecionado);
            } catch (_) {
                if (requestId !== municipiosRequestId) return;
                limparMunicipios('Não foi possível carregar os municípios', true);
            }
        };

        estado.addEventListener('change', () => carregarMunicipios(estado.value));
        form.addEventListener('submit', event => {
            if (municipio.dataset.loading === 'true') {
                event.preventDefault();
            }
        });

        fetch(form.dataset.estadosUrl, { headers: { Accept: 'application/json' } })
            .then(resposta => resposta.ok ? resposta.json() : Promise.reject())
            .then(estados => {
                estados.forEach(item => addOption(estado, item.id, `${item.nome} (${item.sigla})`));
                if (estadoSelecionado) {
                    estado.value = String(estadoSelecionado);
                    return carregarMunicipios(estado.value, municipioSelecionado);
                }
            })
            .catch(() => {
                addOption(estado, '', 'Não foi possível carregar os estados');
            });
    </script>
    {{-- Modal dos termos --}}
    <div class="cartas-terms-modal" id="termsModal" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
        <div class="cartas-terms-modal__backdrop"></div>
        <div class="cartas-terms-modal__dialog">
            <div class="cartas-terms-modal__brand">
                <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar">
            </div>

            <h1 id="termsModalTitle">Termos de uso</h1>

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

            <button type="button" class="cartas-button" id="closeTermsModal" style="margin-top:12px;">Fechar</button>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .cartas-terms-check {
            width: 100%;
            margin: 4px 0 8px;
        }

        .cartas-terms-check__label {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 14px;
            color: #333;
            cursor: pointer;
            line-height: 1.4;
        }

        .cartas-terms-check__label input[type="checkbox"] {
            width: 18px;
            height: 18px;
            min-width: 18px;
            margin-top: 2px;
            cursor: pointer;
            accent-color: #a800d6;
        }

        .cartas-terms-check__link {
            color: #a800d6;
            text-decoration: underline;
            font-weight: 700;
        }

        .cartas-terms-check__link:hover {
            color: #8000a5;
        }

        .cartas-button:disabled {
            opacity: .45;
            cursor: not-allowed;
        }

        .cartas-terms-modal {
            position: fixed;
            inset: 0;
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .cartas-terms-modal.is-open {
            display: flex;
        }

        .cartas-terms-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .68);
            backdrop-filter: blur(7px);
        }

        .cartas-terms-modal__dialog {
            position: relative;
            width: min(100%, 650px);
            max-height: min(700px, calc(100vh - 48px));
            display: flex;
            flex-direction: column;
            border-radius: 8px;
            background: #f1eeeb;
            padding: 24px;
            box-shadow: 0 22px 60px rgba(0, 0, 0, .32);
            color: #111;
        }

        .cartas-terms-modal__brand {
            height: 72px;
            border-radius: 6px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }

        .cartas-terms-modal__brand img {
            width: 112px;
            height: auto;
        }

        .cartas-terms-modal h1 {
            margin: 0 0 14px;
            font-size: 20px;
            line-height: 1.25;
            font-weight: 800;
        }

        .cartas-terms-modal__content {
            flex: 1;
            overflow-y: auto;
            min-height: 0;
            padding-right: 8px; /* space for scrollbar */
        }

        .cartas-terms-modal__content p {
            margin: 0 0 16px;
            font-size: 14px;
            line-height: 1.5;
            color: #333;
        }

        .cartas-terms-modal__content strong {
            color: #000;
            font-size: 15px;
        }


    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const checkbox = document.getElementById('termosCheckbox');
            const submitBtn = document.getElementById('submitBtn');
            const openBtn = document.getElementById('openTermsModal');
            const closeBtn = document.getElementById('closeTermsModal');
            const modal = document.getElementById('termsModal');
            const backdrop = modal?.querySelector('.cartas-terms-modal__backdrop');

            checkbox?.addEventListener('change', () => {
                submitBtn.disabled = !checkbox.checked;
            });

            openBtn?.addEventListener('click', (e) => {
                e.preventDefault();
                modal?.classList.add('is-open');
            });

            closeBtn?.addEventListener('click', () => {
                modal?.classList.remove('is-open');
            });

            backdrop?.addEventListener('click', () => {
                modal?.classList.remove('is-open');
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    modal?.classList.remove('is-open');
                }
            });
        });
    </script>
@endpush

