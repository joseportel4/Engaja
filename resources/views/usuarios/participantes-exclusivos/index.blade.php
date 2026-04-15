@extends('layouts.app')

@section('content')
<div class="mb-4">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
      <p class="text-uppercase text-muted small mb-1">Pessoas</p>
      <h1 class="h4 fw-bold text-engaja mb-2">Participantes exclusivos por ação</h1>
      <p class="text-muted mb-0">
        Selecione as ações pedagógicas para listar pessoas que possuem inscrição ou presença confirmada somente nelas.
      </p>
    </div>
    <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Voltar</a>
  </div>

  @if($errors->has('eventos'))
    <div class="alert alert-danger">{{ $errors->first('eventos') }}</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="GET" action="{{ route('usuarios.participantes-exclusivos.resultado') }}">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div>
            <h2 class="h6 fw-bold mb-1">Ações pedagógicas</h2>
            <div class="text-muted small">{{ $eventos->count() }} ação(ões) disponível(is)</div>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="selecionar-todas-acoes">Selecionar todas</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="limpar-acoes">Limpar</button>
            <button type="submit" class="btn btn-engaja btn-sm">Fazer checagem</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-3" style="width: 48px;"></th>
                <th>Ação pedagógica</th>
                <th class="text-center" style="width: 130px;">Momentos</th>
                <th style="width: 170px;">Período</th>
              </tr>
            </thead>
            <tbody>
              @forelse($eventos as $evento)
                @php
                  $periodo = $evento->data_inicio || $evento->data_fim
                    ? trim(($evento->data_inicio ? \Illuminate\Support\Carbon::parse($evento->data_inicio)->format('d/m/Y') : '') . ' - ' . ($evento->data_fim ? \Illuminate\Support\Carbon::parse($evento->data_fim)->format('d/m/Y') : ''), ' -')
                    : ($evento->data_horario ? \Illuminate\Support\Carbon::parse($evento->data_horario)->format('d/m/Y') : '-');
                @endphp
                <tr>
                  <td class="ps-3">
                    <input
                      type="checkbox"
                      name="eventos[]"
                      value="{{ $evento->id }}"
                      id="evento-{{ $evento->id }}"
                      class="form-check-input js-evento-checkbox"
                      @checked(in_array($evento->id, $selecionados, true))>
                  </td>
                  <td>
                    <label class="fw-semibold mb-0 d-block" for="evento-{{ $evento->id }}">{{ $evento->nome }}</label>
                  </td>
                  <td class="text-center">{{ $evento->atividades_count }}</td>
                  <td>{{ $periodo }}</td>
                </tr>
              @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-4">Nenhuma ação pedagógica cadastrada.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const checks = Array.from(document.querySelectorAll('.js-evento-checkbox'));
    const selecionarTodas = document.getElementById('selecionar-todas-acoes');
    const limpar = document.getElementById('limpar-acoes');

    selecionarTodas?.addEventListener('click', () => checks.forEach(check => check.checked = true));
    limpar?.addEventListener('click', () => checks.forEach(check => check.checked = false));
  });
</script>
@endpush
