@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">Participantes do agendamento</h1>
      <div class="text-muted small">
        {{ optional($agendamento->data_horario)->format('d/m/Y H:i') }} · {{ $agendamento->atividadeAcao?->nome ?? 'Atividade/Ação' }}
      </div>
    </div>
    @unless($agendamento->efetivado)
      <div class="d-flex gap-2">
        <a href="{{ route('agendamentos.participantes.import', $agendamento) }}" class="btn btn-outline-primary">Importar XLSX</a>
        <a href="{{ route('agendamentos.participantes.create', $agendamento) }}" class="btn btn-engaja">Novo participante</a>
      </div>
    @endunless
  </div>

  <div class="alert alert-light border mb-3">
    <strong>Agendamento selecionado:</strong>
    {{ optional($agendamento->data_horario)->format('d/m/Y H:i') }} ·
    {{ $agendamento->atividadeAcao?->nome ?? 'Atividade/Ação' }} ·
    {{ $agendamento->local_acao }}
    @if($agendamento->turma)
      · Turma {{ $agendamento->turma }}
    @endif
  </div>

  <form method="GET" action="{{ route('agendamentos.participantes.index', $agendamento) }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-6">
      <label class="form-label mb-1">Buscar</label>
      <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nome, e-mail, CPF ou telefone">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-1">Por página</label>
      <select name="per_page" class="form-select form-select-sm">
        @foreach([25, 50, 100] as $pp)
          <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-sm btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2 d-grid">
      <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm align-middle table-bordered bg-white">
      <thead class="table-light text-center">
        <tr>
          <th>Nome</th>
          <th>CPF</th>
          <th>E-mail</th>
          <th>Nascimento</th>
          <th>Telefone</th>
          <th>Vínculo</th>
          <th>Turma</th>
          <th>Origem</th>
          @unless($agendamento->efetivado)
            <th class="text-center">Ações</th>
          @endunless
        </tr>
      </thead>
      <tbody>
        @forelse($participantes as $participante)
          <tr>
            <td>{{ $participante->nome }}</td>
            <td>{{ $participante->cpf ?: '—' }}</td>
            <td>{{ $participante->email ?: '—' }}</td>
            <td>{{ $participante->data_nascimento ? \Illuminate\Support\Carbon::parse($participante->data_nascimento)->format('d/m/Y') : '—' }}</td>
            <td>{{ $participante->telefone ?: '—' }}</td>
            <td>{{ $participante->vinculo ?: '—' }}</td>
            <td>{{ $participante->turma ?: '—' }}</td>
            <td>{{ $participante->origem }}</td>
            @unless($agendamento->efetivado)
              <td class="text-center">
                <a href="{{ route('agendamentos.participantes.edit', [$agendamento, $participante]) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                <form method="POST" action="{{ route('agendamentos.participantes.destroy', [$agendamento, $participante]) }}" class="d-inline">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Tem certeza que deseja excluir este participante?')">Excluir</button>
                </form>
              </td>
            @endunless
          </tr>
        @empty
          <tr>
            <td colspan="{{ $agendamento->efetivado ? 8 : 9 }}" class="text-center text-muted py-4">Nenhum participante cadastrado para este agendamento.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center mt-2">
    <div class="small text-muted">Total: {{ $participantes->total() }}</div>
    {{ $participantes->links() }}
  </div>
</div>
@endsection
