@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Indicadores</h1>
  <a href="{{ route('indicadors.create') }}" class="btn btn-engaja">Novo indicador</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Descrição</th>
          <th>Dimensão</th>
          <th class="text-center">Qtd. questões</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($indicadors as $indicador)
        <tr>
          <td class="fw-semibold">{{ $indicador->descricao }}</td>
          <td>{{ $indicador->dimensao->descricao ?? '—' }}</td>
          <td class="text-center">{{ $indicador->questoes_count }}</td>
          <td class="text-end">
            <a href="{{ route('indicadors.show', $indicador) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('indicadors.edit', $indicador) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('indicadors.destroy', $indicador) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir este indicador?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted py-4">Nenhum indicador cadastrado.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $indicadors->links() }}
</div>
@endsection
