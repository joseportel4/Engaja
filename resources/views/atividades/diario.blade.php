@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="mb-4">
            <a href="{{ route('atividades.show', $atividade) }}" class="btn btn-outline-secondary mb-2">
                &larr; Voltar para o momento
            </a>
            <h1 class="h3 fw-bold text-engaja mb-1">Diário de Presenças</h1>
            <p class="text-muted">
                Momento: <strong>{{ $atividade->descricao ?? 'Momento' }}</strong><br>
                Ação: {{ $atividade->evento->nome }}
            </p>
        </div>

        <form action="{{ route('atividades.diario.salvar', $atividade) }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 1rem; overflow: hidden;">
                <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                    <span class="fw-semibold text-muted">Lista de Inscritos ({{ $inscricoes->count() }})</span>
                    <div class="form-check m-0">
                        <input class="form-check-input" type="checkbox" id="check-all" style="cursor: pointer;">
                        <label class="form-check-label fw-medium user-select-none" for="check-all" style="cursor: pointer;">
                            Marcar todos
                        </label>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 60vh;">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light position-sticky top-0" style="z-index: 1;">
                        <tr>
                            <th class="ps-4" style="width: 50px;">Presente</th>
                            <th>Nome do Participante</th>
                            <th>E-mail</th>
                            <th>Município</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($inscricoes as $inscricao)
                            @php
                                $u = $inscricao->participante->user ?? null;
                                $m = $inscricao->participante->municipio ?? null;
                                $uf = $m?->estado?->sigla;
                                $munLabel = $m ? ($m->nome . ($uf ? " - $uf" : "")) : '—';
                                $isMarcado = in_array($inscricao->id, $presencasAtuais);
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="form-check">
                                        <input class="form-check-input check-participante"
                                               type="checkbox"
                                               name="inscricoes[]"
                                               value="{{ $inscricao->id }}"
                                               id="check_{{ $inscricao->id }}"
                                               style="cursor: pointer; transform: scale(1.2);"
                                            {{ $isMarcado ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <label for="check_{{ $inscricao->id }}" style="cursor: pointer; width: 100%;" class="fw-semibold mb-0">
                                        {{ $u->name ?? '—' }}
                                    </label>
                                </td>
                                <td>{{ $u->email ?? '—' }}</td>
                                <td class="text-muted">{{ $munLabel }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    Nenhum participante inscrito nesta ação pedagógica.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('atividades.show', $atividade) }}" class="btn btn-light border">Cancelar</a>
                <button type="submit" class="btn btn-engaja px-4 fw-semibold">
                    Salvar Diário
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('check-all');
            const checkboxes = document.querySelectorAll('.check-participante');

            if (checkAll) {
                checkAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => {
                        cb.checked = this.checked;
                    });
                });
            }
        });
    </script>
@endpush
