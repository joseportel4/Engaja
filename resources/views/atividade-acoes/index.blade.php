@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Atividades/Ações</h1>
  <a href="{{ route('atividade-acoes.create') }}" class="btn btn-engaja">Nova atividade/ação</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th>Turmas</th>
          <th>Detalhe</th>
          <th>Agendamentos</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($atividadeAcoes as $atividadeAcao)
          @php
            $turmas = $atividadeAcao->turmas_configuradas;
            $turmasLabel = $atividadeAcao->usa_turmas
              ? (count($turmas) ? implode(', ', $turmas) : 'Sem turmas configuradas')
              : 'Não utiliza turmas';
          @endphp
          <tr>
            <td class="fw-semibold">{{ $atividadeAcao->nome }}</td>
            <td>{{ $turmasLabel }}</td>
            <td>{{ \Illuminate\Support\Str::limit(strip_tags($atividadeAcao->detalhe), 80) ?: '' }}</td>
            <td>{{ $atividadeAcao->agendamentos_count }}</td>
            <td class="text-end">
              <a href="{{ route('atividade-acoes.show', $atividadeAcao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
              <a href="{{ route('atividade-acoes.edit', $atividadeAcao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
              <form action="{{ route('atividade-acoes.destroy', $atividadeAcao) }}" method="POST" class="d-inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Tem certeza que deseja excluir esta atividade/ação?')">Excluir</button>
              </form>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">Nenhuma atividade/ação cadastrada.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $atividadeAcoes->links() }}
</div>
@endsection

