@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Dimensões</h1>
  <a href="{{ route('dimensaos.create') }}" class="btn btn-engaja">Nova dimensão</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Descrição</th>
          <th class="text-center">Qtd. indicadores</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($dimensaos as $dimensao)
        <tr>
          <td class="fw-semibold">{{ $dimensao->descricao }}</td>
          <td class="text-center">{{ $dimensao->indicadores_count }}</td>
          <td class="text-end">
            <a href="{{ route('dimensaos.show', $dimensao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('dimensaos.edit', $dimensao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('dimensaos.destroy', $dimensao) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta dimensão?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Nenhuma dimensão cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $dimensaos->links() }}
</div>
@endsection
