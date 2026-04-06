@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Agendamentos</h1>
  <a href="{{ route('agendamentos.create') }}" class="btn btn-engaja">Novo agendamento</a>
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
          <th>Público participante</th>
          <th>Local da ação</th>
          <th>Cadastro por</th>
          <th>Participantes</th>
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
            <td>{{ $agendamento->publico_participante }}</td>
            <td>{{ $agendamento->local_acao }}</td>
            <td>{{ $agendamento->user?->name ?? '—' }}</td>
            <td>{{ $agendamento->participantes_clonados_count ?? 0 }}</td>
            <td class="text-end">
              <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-primary">Ver</a>
              <a href="{{ route('agendamentos.participantes.index', $agendamento) }}" class="btn btn-sm btn-outline-dark">Participantes</a>
              <a href="{{ route('agendamentos.edit', $agendamento) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
              <form action="{{ route('agendamentos.destroy', $agendamento) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Tem certeza que deseja excluir este agendamento?')">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="text-center text-muted py-4">Nenhum agendamento cadastrado.</td>
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