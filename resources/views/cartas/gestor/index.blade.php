@extends('cartas.layouts.app')

@section('title', 'Cadastro de cartas - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-manager">
        <section class="cpe-manager__left">
        @include('cartas.shared._logo')

            <div class="cpe-manager__form-wrap">
                <h1 class="cpe-title">Cadastro de cartas</h1>

                @if ($errors->any())
                    <div class="cpe-alert cpe-alert--error">{{ $errors->first() }}</div>
                @endif

                @if (session('status'))
                    <div class="cpe-alert">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('cartas.cartas.store') }}" enctype="multipart/form-data" class="cpe-manager-form">
                    @csrf
                    <label class="cpe-upload">
                        <input type="file" name="arquivo" required accept=".pdf,application/pdf">
                        <span>
                            <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                            <span class="cpe-upload__hint">PDF (máx. 10MB)</span>
                        </span>
                    </label>

                    @php
                        $remetenteSelecionado = $engajaUsers->firstWhere('id', (int) old('remetente_user_id'));
                    @endphp
                    <div class="cpe-combobox" data-combobox>
                        <input type="hidden" name="remetente_user_id" value="{{ old('remetente_user_id') }}" data-combobox-value>
                        <input type="text" class="cpe-field cpe-combobox__input" placeholder="Remetente"
                            autocomplete="off" role="combobox" aria-expanded="false"
                            value="{{ $remetenteSelecionado?->nome_com_localidade }}" data-combobox-input>
                        <ul class="cpe-combobox__list" role="listbox" data-combobox-list>
                            @foreach($engajaUsers as $engajaUser)
                                <li class="cpe-combobox__option" role="option"
                                    data-value="{{ $engajaUser->id }}" data-label="{{ $engajaUser->nome_com_localidade }}">
                                    {{ $engajaUser->nome_com_localidade }}
                                </li>
                            @endforeach
                            <li class="cpe-combobox__empty" data-combobox-empty hidden>Nenhum participante encontrado.</li>
                        </ul>
                    </div>

                    <button type="submit" class="cpe-button">Enviar carta</button>
                </form>
            </div>
        </section>

        <section class="cpe-manager__right">
            <div class="cpe-manager__header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 24px;">
                <div class="cpe-manager__titleline" style="margin: 0; display: flex; align-items: center; gap: 12px;">
                    <h2 style="margin: 0; padding: 0; font-size: 26px; font-weight: 600; color: #111;">Todas as cartas</h2>
                    <span style="margin: 0; font-size: 16px; font-weight: 500; color: #222; background: #f4f4f4; padding: 4px 16px; border-radius: 999px;">{{ $cartas->total() }} cartas</span>
                </div>

                <form id="filterForm" method="GET" action="{{ route('cartas.dashboard') }}" style="display: flex; gap: 24px; align-items: stretch; flex-wrap: wrap; background: #fff; padding: 16px 20px; border-radius: 8px; border: 1px solid #eaeaea; box-shadow: 0 2px 4px rgba(0,0,0,0.02); width: 100%; justify-content: space-between;">
                    
                    <div style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px; max-width: 300px;">
                        <label for="municipio_id" style="font-size: 13px; font-weight: 600; color: #111;">Município do Educando:</label>
                        <select id="municipio_id" name="municipio_id" style="height: 40px; box-sizing: border-box; padding: 0 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; background: #fff; color: #111;">
                            <option value="" style="color: #333;">Todos os municípios</option>
                            @foreach($municipios as $mun)
                                <option value="{{ $mun->id }}" style="color: #111;" @selected($municipioId == $mun->id)>{{ $mun->nome }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px; flex: 2; min-width: 250px;">
                        <label for="search_input" style="font-size: 13px; font-weight: 600; color: #111;">Pesquisa de Remetente ou Destinatário:</label>
                        <div style="position: relative; height: 40px; display: flex; align-items: center;">
                            <input id="search_input" type="search" name="q" value="{{ $search }}" placeholder="Digite o nome..." style="height: 100%; width: 100%; box-sizing: border-box; padding: 0 36px 0 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; color: #111;">
                            <button type="submit" aria-label="Pesquisar" style="position: absolute; right: 8px; background: none; border: none; color: #888; cursor: pointer; display: flex; align-items: center; justify-content: center; height: 100%;">
                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </button>
                        </div>
                        <span style="font-size: 11px; color: #666;">Instantâneo (digite 2 letras)</span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 6px; flex: 1; min-width: 200px; justify-content: flex-start;">
                        <label style="font-size: 13px; font-weight: 600; color: #111;">Ações:</label>
                        <div style="display: flex; gap: 8px;">
                            <button type="submit" form="filterForm" formaction="{{ route('cartas.download-batch') }}" style="display: flex; align-items: center; justify-content: center; white-space: nowrap; padding: 0 16px; border-radius: 6px; height: 40px; box-sizing: border-box; text-decoration: none; background-color: var(--cartas-purple, #6a1b9a); color: white; font-weight: 500; font-size: 14px; border: none; cursor: pointer; transition: opacity 0.2s;">
                                Baixar Cartas PDF
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="cpe-table-card cpe-manager-table">
                <table class="cpe-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Remetente</th>
                            <th>Município do Remetente</th>
                            <th>Destinatário</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cartas as $carta)
                            @php
                                $ultimoStatus = $carta->ultimaMensagem?->status ?? $carta->status;
                                $statusClass = str_contains($ultimoStatus, 'aprovada') || $carta->status === 'respondida'
                                    ? 'cpe-pill--green'
                                    : (str_contains($ultimoStatus, 'verificacao') ? 'cpe-pill--yellow' : 'cpe-pill--blue');
                                $statusLabel = match (true) {
                                    $carta->status === 'respondida' => 'Respondida',
                                    $carta->status === 'aguardando_ajuste' || $ultimoStatus === 'ajuste_solicitado' => 'Ajuste solicitado',
                                    str_contains($ultimoStatus, 'verificacao') => 'Em preparação',
                                    default => 'Enviada',
                                };
                            @endphp
                            <tr>
                                <td>{{ $carta->codigo }}</td>
                                <td>
                                    <span class="cpe-pill {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td>{{ $carta->educando?->nome ?? 'Remetente' }}</td>
                                <td>{{ $carta->educando?->municipio_estado ?? 'Não informado' }}</td>
                                <td>{{ $carta->voluntario?->nome ?? 'Sem voluntário' }}</td>
                                <td>{{ optional($carta->created_at)->format('d/m/Y') }}</td>
                                <td>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="{{ route('cartas.cartas.show', $carta) }}" class="cpe-icon-button" aria-label="Abrir carta" title="Visualizar">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </a>
                                        <button type="button" class="cpe-trash-button" aria-label="Remover carta" title="Excluir" data-modal-open="deleteCarta-{{ $carta->id }}">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                                <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                                <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="cpe-empty">Nenhuma carta cadastrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="cpe-pagination">
                {{ $cartas->links() }}
            </div>
        </section>

        @include('cartas.shared._user-menu')

        @foreach($cartas as $carta)
            <div class="cpe-modal" id="deleteCarta-{{ $carta->id }}">
                <div class="cpe-modal__backdrop"></div>
                <div class="cpe-modal__dialog">
                    <h2>Excluir carta</h2>
                    <p>Tem certeza que deseja excluir a carta {{ $carta->codigo }}? Esta ação removerá a carta da listagem.</p>

                    <div class="cpe-modal-actions">
                        <button type="button" class="cpe-button cpe-button--ghost" data-modal-close>Cancelar</button>
                        <form method="POST" action="{{ route('cartas.cartas.destroy', $carta) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cpe-button cpe-button--danger">Excluir</button>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-manager {
            display: grid;
            grid-template-columns: minmax(420px, 1fr) minmax(520px, 1fr);
            padding-bottom: 56px;
        }

        .cpe-manager__left {
            min-height: 100%;
            padding: 0 72px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #dfd7d2;
        }

        .cpe-manager__form-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
            padding-bottom: 110px;
        }

        .cpe-manager-form {
            display: grid;
            gap: 14px;
        }

        .cpe-manager__right {
            min-height: 100%;
            background: #fbfbfb;
            display: flex;
            flex-direction: column;
            padding-top: 76px;
            box-sizing: border-box;
        }

        .cpe-manager__header {
            min-height: 66px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 18px 28px;
            border-bottom: 1px solid #ededed;
        }

        .cpe-manager__titleline {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .cpe-manager__titleline h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .cpe-manager__titleline span {
            background: #f1f1f1;
            border-radius: 999px;
            padding: 4px 8px;
            font-size: 11px;
            color: #555;
        }

        #search_input::placeholder {
            color: #333;
            opacity: 1;
        }

        .cpe-search {
            width: min(260px, 100%);
            height: 38px;
            border: 1px solid #ddd;
            border-radius: 999px;
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 10px 0 14px;
        }

        .cpe-search input {
            flex: 1;
            border: 0;
            outline: 0;
            font-size: 14px;
            background: transparent;
        }

        .cpe-search button {
            border: 0;
            background: transparent;
            font-size: 18px;
        }

        .cpe-manager-table {
            border-radius: 0;
            box-shadow: none;
        }

        .cpe-manager-table .cpe-table th,
        .cpe-manager-table .cpe-table td {
            color: #0f0f0f;
        }

        .cpe-combobox__input::placeholder {
            color: #333;
            opacity: 1;
        }

        .cpe-icon-button,
        .cpe-trash-button {
            width: 28px;
            height: 28px;
            border: 0;
            background: transparent;
            color: #555;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            cursor: pointer;
            text-decoration: none;
        }

        .cpe-icon-button:hover,
        .cpe-icon-button:focus {
            color: var(--cartas-purple);
            outline: none;
        }

        .cpe-trash-button:hover,
        .cpe-trash-button:focus {
            color: #9f1d1d;
            outline: none;
        }

        .cpe-empty {
            text-align: center;
            padding: 42px !important;
        }

        .cpe-pagination {
            padding: 12px 28px;
            margin-top: auto;
        }

        @media (max-width: 980px) {
            .cpe-manager {
                grid-template-columns: 1fr;
            }

            .cpe-manager__left {
                min-height: auto;
                padding: 72px 24px 40px;
                border-right: 0;
            }

            .cpe-manager__right {
                min-height: auto;
                padding-top: 0;
            }

            .cpe-manager__form-wrap {
                padding-bottom: 0;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search_input');
            const filterForm = document.getElementById('filterForm');
            const municipioSelect = document.getElementById('municipio_id');
            const tableContainer = document.querySelector('.cpe-manager-table');
            const paginationContainer = document.querySelector('.cpe-pagination');
            let debounceTimer;

            function fetchResults(url) {
                tableContainer.style.opacity = '0.5';
                
                fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    tableContainer.innerHTML = doc.querySelector('.cpe-manager-table').innerHTML;
                    paginationContainer.innerHTML = doc.querySelector('.cpe-pagination').innerHTML;
                    
                    tableContainer.style.opacity = '1';
                })
                .catch(error => {
                    console.error('Erro ao buscar dados:', error);
                    tableContainer.style.opacity = '1';
                });
            }

            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    const submitter = e.submitter;
                    // Ignora a submissão via botão de download de PDF
                    if (submitter && submitter.getAttribute('formaction')) {
                        return;
                    }

                    e.preventDefault();
                    
                    const url = new URL(filterForm.action);
                    const formData = new FormData(filterForm);
                    const searchParams = new URLSearchParams(formData);
                    
                    url.search = searchParams.toString();
                    fetchResults(url);
                    
                    window.history.pushState({}, '', url);
                });
            }

            if (searchInput && filterForm) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    
                    debounceTimer = setTimeout(function() {
                        const val = searchInput.value.trim();
                        if (val === '' || val.length >= 2) {
                            filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
                        }
                    }, 500);
                });
            }

            if (municipioSelect && filterForm) {
                municipioSelect.addEventListener('change', function() {
                    filterForm.dispatchEvent(new Event('submit', { cancelable: true }));
                });
            }

            document.addEventListener('click', function(e) {
                const paginationLink = e.target.closest('.cpe-pagination a');
                if (paginationLink) {
                    e.preventDefault();
                    const url = paginationLink.href;
                    fetchResults(url);
                    window.history.pushState({}, '', url);
                }
            });
            
            window.addEventListener('popstate', function() {
                fetchResults(window.location.href);
            });
        });
    </script>
@endsection
