@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h3 fw-bold text-engaja mb-0">Ações pedagógicas</h1>

            <div class="d-flex align-items-center gap-2">
                @hasanyrole('administrador|gerente')
                <button type="button" class="btn btn-outline-primary" id="btn-emitir-certificados" data-bs-toggle="modal" data-bs-target="#modalEmitirCertificados" disabled>Emitir certificados</button>
                @endhasanyrole
                @hasanyrole('administrador|gerente|eq_pedagogica')
                <a href="{{ route('eventos.create') }}" class="btn btn-engaja">Nova ação pedagógica</a>
                @endhasanyrole
            </div>
        </div>
        {{-- Filtros / busca --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Buscar por nome, tipo, objetivo">
            </div>
            <div class="col-md-3">
                <select name="acao_geral" class="form-select">
                    <option value="">Todas as Ações Gerais</option>
                    @foreach(\App\Models\Evento::ACOES_GERAIS as $key => $label)
                    <option value="{{ $key }}" @selected(request('acao_geral') == $key)>
                        Ação Geral {{ $key }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="de" value="{{ request('de') }}" class="form-control" placeholder="de">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-engaja">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 36px;"><input type="checkbox" id="check-all"></th>
                        <th>Nome</th>
                        <th>Sub-Ação</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th>Criado por</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventos as $ev)
                        <tr>
                            <td><input type="checkbox" class="form-check-input evento-check" value="{{ $ev->id }}"></td>
                            <td class="fw-semibold">
                                <div class="d-flex flex-column align-items-start gap-1">
                                    <span>{{ $ev->nome }}</span>
                                    @php
                                        // Só exibe para registros criados com o novo formulário (não-null)
                                        $checklistSalvo   = $ev->checklist_planejamento;
                                        $checklistsMarcados = is_array($checklistSalvo) ? count($checklistSalvo) : null;
                                        $isPlanejamentoIncompleto = $checklistsMarcados !== null && $checklistsMarcados < 13;
                                    @endphp
                                    @if($isPlanejamentoIncompleto)
                                        <a href="{{ route('eventos.edit', $ev) }}#checklist"
                                           class="badge bg-warning text-dark border-0 fw-normal text-decoration-none"
                                           style="font-size: 0.75rem;"
                                           title="Clique para retomar e concluir o checklist de planejamento ({{ $checklistsMarcados }}/13 itens marcados).">
                                            ⚠️ Planejamento incompleto
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if ($ev->subacao)
                                    <span class="badge bg-secondary-subtle text-secondary border fw-normal text-wrap text-start" style="max-width: 250px;" title="{{ $ev->subacao }}">
                                        {{ \Illuminate\Support\Str::limit($ev->subacao, 45) }}
                                    </span>
                                @elseif ($ev->acao_geral)
                                    <small class="text-muted">Ação Geral {{ $ev->acao_geral }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $ev->tipo ?? '-' }}</td>
                            <td>
                                @php
                                    $inicio = $ev->data_inicio ? \Carbon\Carbon::parse($ev->data_inicio)->format('d/m/Y') : null;
                                    $fim = $ev->data_fim ? \Carbon\Carbon::parse($ev->data_fim)->format('d/m/Y') : null;
                                    $mostrarFim = $fim && (!$inicio || $fim !== $inicio);
                                @endphp
                                @if($inicio || $fim)
                                    {{ $inicio ?? '-' }} @if($mostrarFim)<br><small class="text-muted">até {{ $fim }}</small>@endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $ev->user->name ?? '-' }}</td>
                            <td class="text-end text-nowrap">
                                <div class="d-inline-flex gap-2 align-items-center">
                                    <a href="{{ route('eventos.show', $ev) }}" class="btn btn-sm btn-outline-primary">
                                        Ver
                                    </a>

                                    <a href="{{ route('eventos.planejamento.pdf', $ev) }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-danger" title="Ver Planejamento da Ação">Ver Planejamento da Ação</a>

                                    @can('update', $ev)
                                        @hasanyrole('administrador|gerente|eq_pedagogica')
                                        <a href="{{ route('eventos.edit', $ev) }}" class="btn btn-sm btn-outline-secondary">
                                            Editar
                                        </a>
                                        @role('administrador')
                                        <form action="{{ route('eventos.destroy', $ev) }}" method="POST" class="d-inline m-0 p-0"
                                            data-confirm="Tem certeza que deseja excluir esta ação pedagógica?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                        </form>
                                        @endrole
                                        @endhasanyrole
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhuma ação pedagógica encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $eventos->withQueryString()->links() }}
    </div>

    @hasanyrole('administrador|gerente')
    <form method="POST" action="{{ route('certificados.emitir.preparar') }}">
        @csrf
        <input type="hidden" name="eventos" id="eventosSelecionados">
        <div class="modal fade" id="modalEmitirCertificados" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Emitir certificados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">Selecione o modelo que será usado para emitir os certificados das ações selecionadas.</p>
                        <div class="mb-3">
                            <label class="form-label" for="modelo_id">Modelo de certificado</label>
                            <select name="modelo_id" id="modelo_id" class="form-select" required>
                                <option value="">-- Escolha um modelo --</option>
                                @foreach($modelosCertificados as $modelo)
                                    <option value="{{ $modelo->id }}">{{ $modelo->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-check form-switch mt-3 mb-2 p-3 bg-light border rounded">
                            <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" name="unificar" id="unificarSwitch" value="1" style="transform: scale(1.2);">
                            <label class="form-check-label fw-bold" for="unificarSwitch">Unificar ações para a certificação</label>
                            <div class="text-muted small mt-1">Se ativada, o sistema juntará todos os eventos selecionados em um único certificado por pessoa (somando a carga horária).</div>
                        </div>
                        <div class="alert alert-info small mb-0">
                            Serão substituídas as tags: <code>%participante%</code> (nome do participante) e <code>%acao%</code> (nome da ação pedagógica).
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-outline-primary" id="btn-preview-certificado" disabled>Pré-visualizar</button>
                        <button type="submit" class="btn btn-engaja" id="btn-confirmar-emissao" disabled>Prosseguir</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endhasanyrole

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const STORAGE_KEY = 'eventos_selecionados_certificacao';

            //se a URL não tem parametros, eh porque o usuario clicou no menu. eh limpada a sessao para começar do zero.
            if (!window.location.search) {
                sessionStorage.removeItem(STORAGE_KEY);
            }

            let selectedSet = new Set(JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || []);

            //captura dos elementos html
            const checkAll = document.getElementById('check-all');
            const checks = Array.from(document.querySelectorAll('.evento-check'));
            const inputEventos = document.getElementById('eventosSelecionados');
            const btnEmitir = document.getElementById('btn-emitir-certificados');
            const btnConfirmar = document.getElementById('btn-confirmar-emissao');
            const btnPreview = document.getElementById('btn-preview-certificado');
            const selectModelo = document.getElementById('modelo_id');

            //atualiza todos os botoes e inputs baseado na memoria
            const syncGlobalState = () => {

                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(selectedSet)));

                if (inputEventos) inputEventos.value = Array.from(selectedSet).join(',');

                //habilita/desabilita botao de emitir
                const hasSel = selectedSet.size > 0;
                if (btnEmitir) btnEmitir.disabled = !hasSel;

                //habilita/desabilita botões dentro do modal
                const hasModelo = selectModelo && selectModelo.value;
                const enableModalBtns = hasSel && hasModelo;
                if (btnConfirmar) btnConfirmar.disabled = !enableModalBtns;
                if (btnPreview) btnPreview.disabled = !enableModalBtns;

                //se todos os checkboxes visíveis nesta pagina estiverem marcados, marca o "checkAll"
                if (checkAll && checks.length > 0) {
                    checkAll.checked = checks.every(c => c.checked);
                }
            };

            checks.forEach(c => {
                if (selectedSet.has(c.value)) {
                    c.checked = true;
                }

                c.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        selectedSet.add(e.target.value);
                    } else {
                        selectedSet.delete(e.target.value);
                    }
                    syncGlobalState();
                });
            });

            if (checkAll) {
                checkAll.addEventListener('change', (e) => {
                    const isChecked = e.target.checked;
                    checks.forEach(c => {
                        c.checked = isChecked;
                        if (isChecked) {
                            selectedSet.add(c.value);
                        } else {
                            selectedSet.delete(c.value);
                        }
                    });
                    syncGlobalState();
                });
            }

            //eventos do modal
            if (selectModelo) {
                selectModelo.addEventListener('change', syncGlobalState);
            }

            if (btnPreview) {
                btnPreview.addEventListener('click', () => {
                    if (!selectModelo.value || selectedSet.size === 0) return;
                    const params = new URLSearchParams({
                        modelo_id: selectModelo.value,
                        eventos: Array.from(selectedSet).join(','),
                    });
                    window.open(`{{ route('certificados.preview') }}?${params.toString()}`, '_blank');
                });
            }

            const modalEl = document.getElementById('modalEmitirCertificados');
            if (btnEmitir && modalEl) {
                btnEmitir.addEventListener('click', (e) => {
                    if (btnEmitir.disabled) {
                        e.preventDefault();
                        return;
                    }
                    const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalInstance.show();
                });
            }

            //roda a sincronizacao uma vez ao carregar para garantir que o estado inicie perfeito
            syncGlobalState();
        });
    </script>
@endpush
@endsection
