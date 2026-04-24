@extends('layouts.app')

@section('content')
<div class="mb-4">
  <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
    <div>
      <p class="text-uppercase text-muted small mb-1">Pessoas</p>
      <h1 class="h4 fw-bold text-engaja mb-2">Usuários sem vínculo</h1>
      <p class="text-muted mb-0">
        Usuários sem vínculo com ações pedagógicas ou momentos ativos no sistema.
      </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="{{ route('usuarios.index') }}" class="btn btn-light border">Voltar</a>
      <a href="{{ route('usuarios.sem-vinculo.exportar') }}" class="btn btn-engaja">Exportar XLSX</a>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <div class="text-muted">
        <strong>{{ $usuarios->total() }}</strong> usuário(s) sem vínculo encontrado(s)
        @if($usuarios->hasPages())
          | página {{ $usuarios->currentPage() }} de {{ $usuarios->lastPage() }}
        @endif
      </div>
      <div class="text-muted small mt-2">
        A checagem ignora ações pedagógicas e momentos excluídos.
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">Nome</th>
              <th>Email</th>
              <th>CPF</th>
              <th>Telefone</th>
              <th>Município</th>
              <th>Instituição</th>
            </tr>
          </thead>
          <tbody>
            @forelse($usuarios as $usuario)
              <tr>
                <td class="ps-4">
                  <div class="fw-semibold">{{ $usuario->name }}</div>
                  @if($usuario->participante?->tag)
                    <div class="text-muted small">{{ $usuario->participante->tag }}</div>
                  @endif
                </td>
                <td>{{ $usuario->email }}</td>
                <td>{{ $usuario->participante?->cpf ?? '-' }}</td>
                <td>{{ $usuario->participante?->telefone ?? '-' }}</td>
                <td>
                  @php
                    $municipio = $usuario->participante?->municipio?->nome;
                    $estado = $usuario->participante?->municipio?->estado?->sigla;
                  @endphp
                  @if($municipio && $estado)
                    {{ $municipio }} - {{ $estado }}
                  @else
                    {{ $municipio ?? $estado ?? '-' }}
                  @endif
                </td>
                <td>
                  <div>{{ $usuario->participante?->escola_unidade ?? '-' }}</div>
                  @if($usuario->participante?->tipo_organizacao)
                    <div class="text-muted small">{{ $usuario->participante->tipo_organizacao }}</div>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Nenhum usuário sem vínculo encontrado.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if($usuarios->hasPages())
      <div class="card-footer bg-white d-flex justify-content-center overflow-auto py-3 border-top-0">
        {{ $usuarios->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
