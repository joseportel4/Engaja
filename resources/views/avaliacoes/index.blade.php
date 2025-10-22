@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Avaliações</h1>
  <a href="{{ route('avaliacoes.create') }}" class="btn btn-engaja">Nova avaliação</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Participante</th>
          <th>Evento / Atividade</th>
          <th>Modelo</th>
          <th>Registrada em</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($avaliacoes as $avaliacao)
        <tr>
          <td>
            <span class="fw-semibold">
              {{ $avaliacao->inscricao->participante->user->name ?? '—' }}
            </span>
            <small class="d-block text-muted">
              {{ $avaliacao->inscricao->evento->nome ?? 'Sem evento' }}
            </small>
          </td>
          <td>
            <span>{{ $avaliacao->atividade->descricao ?? '—' }}</span>
            <small class="d-block text-muted">
              {{ $avaliacao->atividade && $avaliacao->atividade->dia ? \Illuminate\Support\Carbon::parse($avaliacao->atividade->dia)->format('d/m/Y') : '' }}
              {{ $avaliacao->atividade->hora_inicio ?? '' }}
            </small>
          </td>
          <td>{{ $avaliacao->templateAvaliacao->nome ?? '—' }}</td>
          <td>{{ $avaliacao->created_at ? $avaliacao->created_at->format('d/m/Y H:i') : '—' }}</td>
          <td class="text-end">
            <a href="{{ route('avaliacoes.show', $avaliacao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('avaliacoes.edit', $avaliacao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('avaliacoes.destroy', $avaliacao) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="5" class="text-center text-muted py-4">Nenhuma avaliação registrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $avaliacoes->links() }}
</div>
@endsection
