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
                        <input type="file" name="arquivo" required accept=".pdf,image/*">
                        <span>
                            <span class="cpe-upload__icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M12 16V4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M7 9l5-5 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                            <span class="cpe-upload__link">Clique para selecionar o arquivo</span>
                            <span class="cpe-upload__hint">PDF, PNG, JPG ou GIF (max. 10MB)</span>
                        </span>
                    </label>

                    @php
                        $remetenteSelecionado = $engajaUsers->firstWhere('id', (int) old('remetente_user_id'));
                    @endphp
                    <div class="cpe-combobox" data-combobox>
                        <input type="hidden" name="remetente_user_id" value="{{ old('remetente_user_id') }}" data-combobox-value>
                        <input type="text" class="cpe-field cpe-combobox__input" placeholder="Remetente"
                            autocomplete="off" role="combobox" aria-expanded="false"
                            value="{{ $remetenteSelecionado?->name }}" data-combobox-input>
                        <ul class="cpe-combobox__list" role="listbox" data-combobox-list>
                            @foreach($engajaUsers as $engajaUser)
                                <li class="cpe-combobox__option" role="option"
                                    data-value="{{ $engajaUser->id }}" data-label="{{ $engajaUser->name }}">
                                    {{ $engajaUser->name }}
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
            <div class="cpe-manager__header">
                <div class="cpe-manager__titleline">
                    <h2>Todas as cartas</h2>
                    <span>{{ $cartas->total() }} cartas</span>
                </div>

                <form method="GET" action="{{ route('cartas.dashboard') }}" class="cpe-search">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Pesquisar">
                    <button type="submit" aria-label="Pesquisar">⌕</button>
                </form>
            </div>

            <div class="cpe-table-card cpe-manager-table">
                <table class="cpe-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Status</th>
                            <th>Remetente</th>
                            <th>Destinatário</th>
                            <th>Data</th>
                            <th></th>
                            <th></th>
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
                                    str_contains($ultimoStatus, 'verificacao') => 'Em verificação',
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
                                <td>{{ $carta->educando?->user?->name ?? 'Remetente' }}</td>
                                <td>{{ $carta->voluntario?->name ?? 'Sem voluntário' }}</td>
                                <td>{{ optional($carta->created_at)->format('d/m/Y') }}</td>
                                <td><a href="{{ route('cartas.cartas.show', $carta) }}" class="cpe-link">Abrir</a></td>
                                <td>
                                    <button type="button" class="cpe-trash-button" aria-label="Remover carta" data-modal-open="deleteCarta-{{ $carta->id }}">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M3 6h18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M8 6V4h8v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6 6l1 15h10l1-15" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>
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
        }

        .cpe-manager__left {
            min-height: 100vh;
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
            min-height: 100vh;
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
@endsection
