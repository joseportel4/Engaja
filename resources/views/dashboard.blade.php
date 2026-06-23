@extends('layouts.app')

@section('content')
<style>
    .transition-icon {
        transition: transform 0.2s ease-in-out;
        display: inline-block;
    }
    [aria-expanded="true"] .transition-icon {
        transform: rotate(90deg);
    }
    .btn-engaja-outline {
        color: #421944;
        border-color: #421944;
    }
    .btn-engaja-outline:hover {
        background-color: #421944;
        color: #fff;
    }
</style>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <p class="text-uppercase small text-muted mb-1">Dashboards</p>
            <h1 class="h4 mb-0">Inscrições, Presenças e Ausências</h1>
            <p class="text-muted small mb-0">Visual completo das ações pedagógicas com expansão de presenças e exportação em PDF.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Hub de dashboards</a>
            <a href="{{ route('dashboards.bi') }}" class="btn btn-outline-secondary btn-sm">Dashboard BI</a>
            <a href="{{ route('dashboards.avaliacoes') }}" class="btn btn-outline-primary btn-sm">Dashboard de respostas</a>
        </div>
    </div>
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Ação pedagógica (evento)</label>
                    <select name="evento_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($eventos as $id => $nome)
                        <option value="{{ $id }}" @selected(request('evento_id')==$id)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">De</label>
                    <input type="date" name="de" value="{{ request('de') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Até</label>
                    <input type="date" name="ate" value="{{ request('ate') }}" class="form-control form-control-sm">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Buscar (momento/ação)</label>
                    <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Digite para filtrar...">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Por página</label>
                    <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="25" @selected(request('per_page', 25)==25)>25</option>
                        <option value="50" @selected(request('per_page')==50)>50</option>
                        <option value="100" @selected(request('per_page')==100)>100</option>
                    </select>
                </div>

                {{-- mantém sort/dir atuais --}}
                <input type="hidden" name="sort" value="{{ request('sort', 'dia') }}">
                <input type="hidden" name="dir" value="{{ request('dir', 'desc') }}">

                <div class="col-md-auto d-flex gap-2">
                    <button class="btn btn-primary btn-sm">Filtrar</button>
                    <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            {{-- barra de ações da tabela --}}
            <div class="d-flex justify-content-end gap-2 p-2 border-bottom bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm js-toggle-all" data-action="show">
                    Expandir todos
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm js-toggle-all" data-action="hide">
                    Recolher todos
                </button>
                <button type="button"
                    class="btn btn-outline-primary btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#exportPdfModal">
                    Baixar Listas (PDF)
                </button>
                <button type="button"
                    class="btn btn-outline-success btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#exportExcelModal">
                    <i class="fas fa-file-excel me-1"></i>Matriz de Presença (Excel)
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light">
                        @php
                        function sort_link($label,$key){
                        $curr = request('sort','dia');
                        $dir = request('dir','desc') === 'asc' ? 'asc' : 'desc';
                        $next = ($curr===$key && $dir==='asc') ? 'desc' : 'asc';
                        $params = array_merge(request()->except('page'), ['sort'=>$key,'dir'=>$next]);
                        $url = request()->url().'?'.http_build_query($params);
                        $is = $curr===$key;
                        $arrow = $is ? ($dir==='asc' ? '↑' : '↓') : '';
                        return '<a href="'.$url.'" class="text-decoration-none">'.$label.' <span class="text-muted">'.$arrow.'</span></a>';
                        }
                        @endphp
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="min-width:110px;">{!! sort_link('Data','dia') !!}</th>
                            <th style="min-width:80px;">{!! sort_link('Hora','hora') !!}</th>
                            <th>{!! sort_link('Momento','momento') !!}</th>
                            <th>{!! sort_link('Município','municipio') !!}</th>
                            <th>{!! sort_link('Ação pedagógica','acao') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Inscritos','inscritos') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Presentes','presentes') !!}</th>
                            <th class="text-end" style="min-width:90px;">{!! sort_link('Ausentes','ausentes') !!}</th>
                            <th class="text-center" style="width: 130px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($atividades as $a)
                        @php
                        $data = \Carbon\Carbon::parse($a->dia)->format('d/m/Y');
                        $hora = \Illuminate\Support\Str::of($a->hora_inicio)->substr(0,5);
                        $collapseId = 'pres-' . $a->id;
                        $inscritosCount = $a->inscritos_count ?? 0;
                        $presentesCount = $a->presentes_count ?? 0;
                        $ausentesCount = max($inscritosCount - $presentesCount, 0);
                        @endphp

                        <tr>
                            <td class="text-center">
                                <a class="text-muted d-block"
                                   data-bs-toggle="collapse"
                                   href="#{{ $collapseId }}"
                                   role="button"
                                   aria-expanded="false"
                                   aria-controls="{{ $collapseId }}">
                                    <i class="fas fa-chevron-right transition-icon"></i>
                                </a>
                            </td>
                            <td>{{ $data }}</td>
                            <td>{{ $hora }}</td>
                            <td>{{ $a->descricao ?? 'Momento' }}</td>
                            <td>{{ $a->municipio?->nome_com_estado ?? '-' }}</td>
                            <td>{{ $a->evento_nome ?? $a->evento->nome ?? '-' }}</td>
                            <td class="text-end fw-semibold">{{ $inscritosCount }}</td>
                            {{-- Gatilho do accordion na coluna Presentes --}}
                            <td class="text-end">
                                <a class="badge bg-success text-decoration-none"
                                    data-bs-toggle="collapse"
                                    href="#{{ $collapseId }}"
                                    role="button"
                                    data-focus-target="#{{ $collapseId }}-participantes"
                                    aria-expanded="false"
                                    aria-controls="{{ $collapseId }}">
                                    {{ $presentesCount }}
                                </a>
                            </td>
                            <td class="text-end">
                                @if($ausentesCount > 0)
                                    <a class="badge bg-warning text-dark text-decoration-none"
                                        data-bs-toggle="collapse"
                                        href="#{{ $collapseId }}"
                                        role="button"
                                        data-focus-target="#{{ $collapseId }}-participantes"
                                        aria-expanded="false"
                                        aria-controls="{{ $collapseId }}">
                                        {{ $ausentesCount }}
                                    </a>
                                @else
                                    <span class="text-muted">0</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-engaja-outline"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#{{ $collapseId }}"
                                        aria-expanded="false"
                                        aria-controls="{{ $collapseId }}">
                                    <i class="fas fa-users me-1"></i> Ver lista
                                </button>
                            </td>
                        </tr>
                        {{-- Linha de detalhes: carregada sob demanda (lazy) ao expandir --}}
                        <tr>
                            <td colspan="10" class="bg-light p-0">
                                <div id="{{ $collapseId }}" class="collapse presentes-collapse"
                                     data-detail-url="{{ route('dashboards.presencas.detalhes', $a->id) }}">
                                    <div class="js-detail-placeholder d-flex align-items-center justify-content-center gap-2 p-3 text-muted small">
                                        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                                        Carregando detalhes...
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted p-4">Nenhuma atividade encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($atividades->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="small text-muted">
                Exibindo {{ $atividades->count() }} de {{ $atividades->total() }}
            </div>
            {{ $atividades->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<div class="modal fade" id="exportExcelModal" tabindex="-1" aria-labelledby="exportExcelModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('dashboard.export.excel') }}">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="exportExcelModalLabel">Baixar Excel (Matriz de Frequência)</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label mb-1">Ação pedagógica <span class="text-danger">*</span></label>
                        <select name="evento_id" class="form-select" required>
                            <option value="">Selecione a ação...</option>
                            @foreach($eventos as $id => $nome)
                                <option value="{{ $id }}">{{ $nome }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">O arquivo Excel será gerado em formato de Matriz de Presença agrupando as listas da Ação pedagógica selecionada por município.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Gerar Planilha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="exportPdfModal" tabindex="-1" aria-labelledby="exportPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="GET" action="{{ route('dashboard.export') }}" target="_blank">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="exportPdfModalLabel">Gerar PDF do dashboard</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label mb-1">Ação pedagógica</label>
                            <select name="pdf_evento_id" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                @foreach($eventos as $id => $nome)
                                    <option value="{{ $id }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Município</label>
                            <select name="pdf_municipio_id" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach(($municipios ?? collect()) as $municipio)
                                    <option value="{{ $municipio->id }}">{{ $municipio->nome_com_estado ?? $municipio->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Momento</label>
                            <select name="pdf_momento" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                @foreach(($momentos ?? collect()) as $momento)
                                    <option value="{{ $momento }}">{{ $momento }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">De</label>
                            <input type="date" name="pdf_de" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1">Até</label>
                            <input type="date" name="pdf_ate" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <div class="d-flex gap-2">
                        <button type="reset" class="btn btn-light btn-sm">Limpar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Gerar PDF</button>
                    </div>
                </div>
                <input type="hidden" name="sort" value="{{ request('sort', 'dia') }}">
                <input type="hidden" name="dir" value="{{ request('dir', 'desc') }}">
            </form>
        </div>
    </div>
</div>

{{-- Script para expandir/recolher todos e lazy loading de detalhes --}}
<script>
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.js-toggle-all');
        if (!btn) return;

        const action = btn.dataset.action; // 'show' | 'hide'
        const items = document.querySelectorAll('.presentes-collapse');
        const hasBootstrap = window.bootstrap && bootstrap.Collapse;

        items.forEach(function(el) {
            if (hasBootstrap) {
                const instance = bootstrap.Collapse.getOrCreateInstance(el, { toggle: false });
                if (action === 'show') instance.show();
                else instance.hide();
            } else {
                if (action === 'show') el.classList.add('show');
                else el.classList.remove('show');
            }
        });
    });

    document.addEventListener('click', function(e) {
        const focusLink = e.target.closest('[data-focus-target]');
        if (!focusLink) return;

        const collapseSelector = focusLink.getAttribute('href');
        if (!collapseSelector || !collapseSelector.startsWith('#')) return;

        const collapseEl = document.querySelector(collapseSelector);
        if (!collapseEl) return;

        collapseEl.dataset.focusTarget = focusLink.dataset.focusTarget || '';
    });

    // Lazy loading: carrega detalhes ao expandir pela primeira vez
    document.addEventListener('show.bs.collapse', function (event) {
        const collapseEl = event.target;
        if (!collapseEl.classList.contains('presentes-collapse')) return;

        const placeholder = collapseEl.querySelector('.js-detail-placeholder');
        if (!placeholder) return; // já carregado

        const url = collapseEl.dataset.detailUrl;
        if (!url) return;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.ok ? r.text() : Promise.reject(r.status); })
            .then(function(html) {
                collapseEl.innerHTML = html;
                const focusSel = collapseEl.dataset.focusTarget;
                if (focusSel) {
                    const focusEl = collapseEl.querySelector(focusSel);
                    if (focusEl) focusEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    delete collapseEl.dataset.focusTarget;
                }
            })
            .catch(function() {
                collapseEl.innerHTML = '<div class="p-3 text-danger small">Erro ao carregar detalhes. Tente expandir novamente.</div>';
            });
    });

    // Scroll para seção focada após animação (quando já carregado)
    if (window.bootstrap && bootstrap.Collapse) {
        document.addEventListener('shown.bs.collapse', function (event) {
            const collapseEl = event.target;
            const focusSel = collapseEl.dataset.focusTarget;
            if (!focusSel) return;
            const focusEl = collapseEl.querySelector(focusSel);
            if (focusEl) focusEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            delete collapseEl.dataset.focusTarget;
        });
    }
</script>

@endsection
