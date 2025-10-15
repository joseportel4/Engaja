@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-10">
    <h1 class="h3 fw-bold text-engaja mb-4">Novo template de avaliação</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('templates-avaliacao.store') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-6">
              <label for="nome" class="form-label">Nome</label>
              <input type="text" id="nome" name="nome"
                class="form-control @error('nome') is-invalid @enderror"
                value="{{ old('nome') }}" required>
              @error('nome')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-6">
              <label for="descricao" class="form-label">Descrição</label>
              <input type="text" id="descricao" name="descricao"
                class="form-control @error('descricao') is-invalid @enderror"
                value="{{ old('descricao') }}">
              @error('descricao')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mt-4">
            <h2 class="h6 fw-semibold text-uppercase text-muted">Questões</h2>
            <p class="text-muted small mb-3">
              Marque as questões que farão parte do template e, opcionalmente, defina a ordem de exibição.
            </p>

            <div class="table-responsive border rounded">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 60px;">Usar?</th>
                    <th>Questão</th>
                    <th>Indicador / Dimensão</th>
                    <th>Tipo</th>
                    <th style="width: 140px;">Ordem</th>
                  </tr>
                </thead>
                <tbody>
                  @php
                    $selecionadas = collect(old('questoes', []))->map(fn($id) => (int) $id)->all();
                    $ordens = old('ordens', []);
                  @endphp
                  @forelse ($questaos as $questao)
                  <tr>
                    <td>
                      <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                          name="questoes[]" id="questao{{ $questao->id }}"
                          value="{{ $questao->id }}" @checked(in_array($questao->id, $selecionadas, true))>
                      </div>
                    </td>
                    <td>
                      <label for="questao{{ $questao->id }}" class="d-block fw-semibold">
                        {{ \Illuminate\Support\Str::limit($questao->texto, 100) }}
                      </label>
                    </td>
                    <td>
                      <span class="d-block">{{ $questao->indicador->descricao ?? '—' }}</span>
                      <small class="text-muted">{{ $questao->indicador->dimensao->descricao ?? '' }}</small>
                    </td>
                    <td>{{ ucfirst($questao->tipo) }}</td>
                    <td>
                      <input type="number" name="ordens[{{ $questao->id }}]" min="1" max="999"
                        class="form-control form-control-sm"
                        value="{{ $ordens[$questao->id] ?? '' }}" placeholder="1, 2, 3...">
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="5" class="text-center text-muted py-4">Cadastre questões antes de montar um template.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
            @error('questoes')
            <div class="text-danger small mt-2">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('templates-avaliacao.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-engaja">Salvar template</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
