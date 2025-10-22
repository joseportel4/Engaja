@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Evidências</h1>
  <a href="{{ route('evidencias.create') }}" class="btn btn-engaja">Nova evidência</a>
  </div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Descrição</th>
          <th>Indicador</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($evidencias as $evidencia)
        <tr>
          <td class="fw-semibold">{{ $evidencia->descricao }}</td>
          <td>
            {{ $evidencia->indicador->dimensao->descricao ?? '' }}
            @if($evidencia->indicador)
              <small class="d-block text-muted">{{ $evidencia->indicador->descricao }}</small>
            @endif
          </td>
          <td class="text-end">
            <a href="{{ route('evidencias.show', $evidencia) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('evidencias.edit', $evidencia) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('evidencias.destroy', $evidencia) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta evidência?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Nenhuma evidência cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $evidencias->links() }}
</div>
@endsection

