@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 fw-bold text-engaja mb-0">Efetuar agendamentos</h1>
    <div class="text-muted">Somente agendamentos ainda não efetivados aparecem aqui.</div>
  </div>
  <a href="{{ route('agendamentos.efetivados.index') }}" class="btn btn-outline-secondary">Ver agendamentos efetivados</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Data e horário</th>
          <th>Município</th>
          <th>Atividade/Ação</th>
          <th>Turma</th>
          <th>Participantes</th>
          <th>Responsável</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($agendamentos as $agendamento)
          <tr>
            <td>{{ optional($agendamento->data_horario)->format('d/m/Y H:i') }}</td>
            <td>{{ $agendamento->municipio?->nome_com_estado ?? '—' }}</td>
            <td>{{ $agendamento->atividadeAcao?->nome ?? '—' }}</td>
            <td>{{ $agendamento->turma ?: '—' }}</td>
            <td>{{ $agendamento->participantes_clonados_count ?? 0 }}</td>
            <td>{{ $agendamento->user?->name ?? '—' }}</td>
            <td class="text-end">
              <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-primary">Ver</a>
              <a href="{{ route('agendamentos.efetivacoes.create', $agendamento) }}" class="btn btn-sm btn-engaja">Efetivar</a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted py-4">Nenhum agendamento pendente de efetivação.</td>
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
