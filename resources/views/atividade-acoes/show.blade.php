@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">{{ $atividadeAcao->nome }}</h1>
      <a href="{{ route('atividade-acoes.edit', $atividadeAcao) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm mb-3">
      <div class="card-body">
        <p class="mb-2">
          <strong>Turmas:</strong>
          @if($atividadeAcao->usa_turmas)
            {{ count($atividadeAcao->turmas_configuradas) ? implode(', ', $atividadeAcao->turmas_configuradas) : 'Sem turmas configuradas' }}
          @else
            Não utiliza turmas
          @endif
        </p>
        <p class="mb-0"><strong>Detalhe:</strong> {{ $atividadeAcao->detalhe ?: 'Sem detalhe' }}</p>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-3">Agendamentos vinculados</h2>

        <ul class="list-group list-group-flush">
          @forelse($atividadeAcao->agendamentos as $agendamento)
            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
              <span>
                {{ optional($agendamento->data_horario)->format('d/m/Y H:i') }} -
                {{ $agendamento->municipio?->nome_com_estado ?? 'Município não informado' }}
                @if($agendamento->turma)
                  - Turma: {{ $agendamento->turma }}
                @endif
              </span>
              <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            </li>
          @empty
            <li class="list-group-item px-0 text-muted">Nenhum agendamento vinculado.</li>
          @endforelse
        </ul>
      </div>
    </div>

    <a href="{{ route('atividade-acoes.index') }}" class="btn btn-link px-0 mt-3">Voltar para lista</a>
  </div>
</div>
@endsection

