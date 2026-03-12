@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Verificar usuário por planilha</h1>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="POST" action="{{ route('usuarios.verificar.processar') }}" enctype="multipart/form-data" class="row g-3 align-items-end">
        @csrf
        <div class="col-12 col-md-8">
          <label for="arquivo" class="form-label">Arquivo (xlsx, xls, csv)</label>
          <input
            type="file"
            name="arquivo"
            id="arquivo"
            class="form-control @error('arquivo') is-invalid @enderror"
            accept=".xlsx,.xls,.csv"
            required>
          @error('arquivo')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-12 col-md-4 d-flex justify-content-md-end">
          <button type="submit" class="btn btn-primary w-100 w-md-auto">Verificar usuários</button>
        </div>
      </form>
    </div>
  </div>

  <div class="mb-4">
    <div class="form-text">
      Envie um arquivo Excel com a primeira linha como cabeçalho.
    </div>
    <div class="form-text">
      Colunas: nome, email, cpf, telefone, municipio,
    </div>
    <div class="mt-2">
      <a href="{{ asset('modelos/modelo_inscricoes_engaja.xlsx') }}" class="btn btn-sm btn-outline-primary">
        📥 Baixar modelo de planilha
      </a>
    </div>
  </div>

  @if($resumo)
    <div class="text-muted mb-3">
      Total de usuários na planilha: <strong>{{ $resumo['total_importacao'] }}</strong>
      |
      Já cadastrados no sistema: <strong>{{ $resumo['usuarios_existentes'] }}</strong>
      |
      Não cadastrados: <strong>{{ $resumo['usuarios_nao_cadastrados'] }}</strong>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="text-muted">
        @if($rows)
          {{ $rows->total() }} usuário(s) não cadastrado(s) |
          Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }} |
          exibindo {{ $rows->count() }} por página
        @endif
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('usuarios.verificar.exportar', ['format' => 'csv', 'session_key' => $sessionKey]) }}" class="btn btn-outline-primary btn-sm">Exportar CSV</a>
        <a href="{{ route('usuarios.verificar.exportar', ['format' => 'xlsx', 'session_key' => $sessionKey]) }}" class="btn btn-primary btn-sm">Exportar XLSX</a>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle table-bordered bg-white">
        <thead class="table-light">
          <tr>
            <th style="min-width:220px;">Nome</th>
            <th style="min-width:240px;">Email</th>
            <th style="min-width:140px;">CPF</th>
            <th style="min-width:140px;">Telefone</th>
            <th style="min-width:260px;">Municipio</th>
            <th style="min-width:220px;">Tipo de Organizacao</th>
            <th style="min-width:220px;">Organizacao</th>
            <th style="min-width:200px;">Tag</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
            <tr>
              <td>{{ $r['nome'] ?? '-' }}</td>
              <td>{{ $r['email'] ?? '-' }}</td>
              <td>{{ $r['cpf'] ?? '-' }}</td>
              <td>{{ $r['telefone'] ?? '-' }}</td>
              <td>{{ $r['municipio'] ?? '-' }}</td>
              <td>{{ $r['tipo_organizacao'] ?? '-' }}</td>
              <td>{{ $r['escola_unidade'] ?? '-' }}</td>
              <td>{{ $r['tag'] ?? '-' }}</td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center text-muted">Todos os usuários da planilha já estão cadastrados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if($rows)
      <div>
        {{ $rows->appends(['session_key' => $sessionKey, 'per_page' => $rows->perPage()])->links() }}
      </div>
    @endif
  @endif
</div>
@endsection
