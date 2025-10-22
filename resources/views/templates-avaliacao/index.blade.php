@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Modelos de avaliação</h1>
  <a href="{{ route('templates-avaliacao.create') }}" class="btn btn-engaja">Novo modelo</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Nome</th>
          <th>Descrição</th>
          <th class="text-center">Qtd. questões</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($templates as $template)
        <tr>
          <td class="fw-semibold">{{ $template->nome }}</td>
          <td>{{ $template->descricao ?: '—' }}</td>
          <td class="text-center">{{ $template->questoes_count }}</td>
          <td class="text-end">
            <a href="{{ route('templates-avaliacao.show', $template) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('templates-avaliacao.edit', $template) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('templates-avaliacao.destroy', $template) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir este modelo?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted py-4">Nenhum modelo cadastrado.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $templates->links() }}
</div>
@endsection
