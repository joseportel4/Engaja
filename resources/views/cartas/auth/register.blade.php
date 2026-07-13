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
            <input id="telefone" class="cartas-field" type="text" name="telefone" value="{{ old('telefone') }}" inputmode="numeric" maxlength="15" placeholder="Digite seu telefone.">
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
        <button type="submit" class="cartas-button">Continuar</button>
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
@endsection
