@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 fw-bold text-engaja mb-0">Agendamentos efetivados</h1>
    <div class="text-muted">Lista apenas os agendamentos já convertidos em momento.</div>
  </div>
  <a href="{{ route('agendamentos.efetivacoes.index') }}" class="btn btn-outline-secondary">Voltar para efetivação</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Efetivado em</th>
          <th>Data do agendamento</th>
          <th>Município</th>
          <th>Atividade/Ação</th>
          <th>Participantes</th>
          <th>Momento criado</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($agendamentos as $agendamento)
          <tr>
            <td>{{ optional($agendamento->efetivado_em)->format('d/m/Y H:i') ?? '—' }}</td>
            <td>{{ optional($agendamento->data_horario)->format('d/m/Y H:i') }}</td>
            <td>{{ $agendamento->municipio?->nome_com_estado ?? '—' }}</td>
            <td>{{ $agendamento->atividadeAcao?->nome ?? '—' }}</td>
            <td>{{ $agendamento->participantes_clonados_count ?? 0 }}</td>
            <td>
              @if($agendamento->atividade)
                <div>{{ $agendamento->atividade->descricao }}</div>
                @if($agendamento->atividade->evento)
                  <div class="small text-muted">{{ $agendamento->atividade->evento->nome }}</div>
                @endif
              @else
                —
              @endif
            </td>
            <td class="text-end">
              <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-primary">Ver</a>
              @if($agendamento->atividade)
                <a href="{{ route('atividades.show', $agendamento->atividade) }}" class="btn btn-sm btn-outline-success">Momento</a>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Nenhum agendamento efetivado encontrado.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $agendamentos->links() }}
</div>
@endsection
