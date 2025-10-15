@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <h1 class="h3 fw-bold text-engaja mb-4">Editar questão</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('questaos.update', $questao) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="indicador_id" class="form-label">Indicador</label>
            <select id="indicador_id" name="indicador_id"
              class="form-select @error('indicador_id') is-invalid @enderror" required>
              <option value="">Selecione...</option>
              @foreach ($indicadores as $id => $descricao)
              <option value="{{ $id }}" @selected(old('indicador_id', $questao->indicador_id) == $id)>{{ $descricao }}</option>
              @endforeach
            </select>
            @error('indicador_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="texto" class="form-label">Enunciado</label>
            <textarea id="texto" name="texto"
              class="form-control @error('texto') is-invalid @enderror" rows="4" required>{{ old('texto', $questao->texto) }}</textarea>
            @error('texto')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="row g-3">
            <div class="col-md-4">
              <label for="tipo" class="form-label">Tipo de resposta</label>
              <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror" required>
                @foreach (['texto' => 'Texto aberto', 'escala' => 'Escala', 'numero' => 'Numérica', 'boolean' => 'Sim/Não'] as $valor => $rotulo)
                <option value="{{ $valor }}" @selected(old('tipo', $questao->tipo) == $valor)>{{ $rotulo }}</option>
                @endforeach
              </select>
              @error('tipo')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-5">
              <label for="escala_id" class="form-label">Escala (quando tipo = Escala)</label>
              <select id="escala_id" name="escala_id"
                class="form-select @error('escala_id') is-invalid @enderror">
                <option value="">Selecione...</option>
                @foreach ($escalas as $id => $descricao)
                <option value="{{ $id }}" @selected(old('escala_id', $questao->escala_id) == $id)>{{ $descricao }}</option>
                @endforeach
              </select>
              @error('escala_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-3 d-flex align-items-end">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="fixa" name="fixa"
                  value="1" @checked(old('fixa', $questao->fixa))>
                <label class="form-check-label" for="fixa">Questão fixa</label>
              </div>
            </div>
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('questaos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-engaja">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
