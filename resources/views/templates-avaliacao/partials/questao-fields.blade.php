@php
  /** @var int|string $index */
  $errorsBag = $errors ?? app('view')->shared('errors');
  $isPrototype = $isPrototype ?? false;
  $questaoId = $questao['id'] ?? null;
  $indicadorErro = ! $isPrototype && $errorsBag->has("questoes.$index.indicador_id");
  $textoErro = ! $isPrototype && $errorsBag->has("questoes.$index.texto");
  $tipoErro = ! $isPrototype && $errorsBag->has("questoes.$index.tipo");
  $escalaErro = ! $isPrototype && $errorsBag->has("questoes.$index.escala_id");
  $ordemErro = ! $isPrototype && $errorsBag->has("questoes.$index.ordem");
  $fixaErro = ! $isPrototype && $errorsBag->has("questoes.$index.fixa");
  $questionPosition = is_numeric($index) ? ((int) $index + 1) : '';
@endphp

<div class="card shadow-sm mb-3 question-item" data-question-card data-index="{{ $index }}" data-existing="{{ $questaoId ? 'true' : 'false' }}">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <h3 class="h6 fw-semibold mb-0">Questão <span class="question-position">{{ $questionPosition }}</span></h3>
      <button type="button" class="btn btn-sm btn-outline-danger js-remove-question">Remover</button>
    </div>

    <input type="hidden" name="questoes[{{ $index }}][id]" value="{{ $questaoId }}">
    <input type="hidden" name="questoes[{{ $index }}][_delete]" value="0" class="question-delete-flag">

    <div class="row g-3 align-items-start">
      <div class="col-md-6">
        <label for="questoes-{{ $index }}-indicador_id" class="form-label">Indicador</label>
        <select id="questoes-{{ $index }}-indicador_id" name="questoes[{{ $index }}][indicador_id]"
          class="form-select{{ $indicadorErro ? ' is-invalid' : '' }}">
          <option value="">Selecione...</option>
          @foreach ($indicadores as $id => $descricao)
          <option value="{{ $id }}" @selected(($questao['indicador_id'] ?? '') == $id)>{{ $descricao }}</option>
          @endforeach
        </select>
        @if ($indicadorErro)
        <div class="invalid-feedback">{{ $errorsBag->first("questoes.$index.indicador_id") }}</div>
        @endif
      </div>

      <div class="col-md-3">
        <label for="questoes-{{ $index }}-tipo" class="form-label">Tipo de resposta</label>
        <select id="questoes-{{ $index }}-tipo" name="questoes[{{ $index }}][tipo]"
          class="form-select{{ $tipoErro ? ' is-invalid' : '' }}" {{ $isPrototype ? '' : 'required' }}>
          @foreach ($tiposQuestao as $valor => $rotulo)
          <option value="{{ $valor }}" @selected(($questao['tipo'] ?? 'texto') === $valor)>{{ $rotulo }}</option>
          @endforeach
        </select>
        @if ($tipoErro)
        <div class="invalid-feedback">{{ $errorsBag->first("questoes.$index.tipo") }}</div>
        @endif
      </div>

      <div class="col-md-3">
        <label for="questoes-{{ $index }}-ordem" class="form-label">Ordem</label>
        <input type="number" id="questoes-{{ $index }}-ordem" name="questoes[{{ $index }}][ordem]" min="1" max="999"
          class="form-control{{ $ordemErro ? ' is-invalid' : '' }}" value="{{ $questao['ordem'] ?? '' }}" placeholder="1, 2, 3...">
        @if ($ordemErro)
        <div class="invalid-feedback">{{ $errorsBag->first("questoes.$index.ordem") }}</div>
        @endif
      </div>
    </div>

    <div class="row g-3 align-items-start mt-1">
      <div class="col-md-8">
        <label for="questoes-{{ $index }}-texto" class="form-label">Enunciado</label>
        <textarea id="questoes-{{ $index }}-texto" name="questoes[{{ $index }}][texto]" rows="3"
          class="form-control{{ $textoErro ? ' is-invalid' : '' }}" {{ $isPrototype ? '' : 'required' }}>{{ $questao['texto'] ?? '' }}</textarea>
        @if ($textoErro)
        <div class="invalid-feedback">{{ $errorsBag->first("questoes.$index.texto") }}</div>
        @endif
      </div>

      <div class="col-md-4 escala-field" data-escala-wrapper>
        <label for="questoes-{{ $index }}-escala_id" class="form-label">Escala (quando tipo = Escala)</label>
        <select id="questoes-{{ $index }}-escala_id" name="questoes[{{ $index }}][escala_id]"
          class="form-select{{ $escalaErro ? ' is-invalid' : '' }}">
          <option value="">Selecione...</option>
          @foreach ($escalas as $id => $descricao)
          <option value="{{ $id }}" @selected(($questao['escala_id'] ?? '') == $id)>{{ $descricao }}</option>
          @endforeach
        </select>
        @if ($escalaErro)
        <div class="invalid-feedback">{{ $errorsBag->first("questoes.$index.escala_id") }}</div>
        @endif
      </div>
    </div>

    <div class="form-check form-switch mt-3">
      <input class="form-check-input{{ $fixaErro ? ' is-invalid' : '' }}" type="checkbox" role="switch"
        id="questoes-{{ $index }}-fixa" name="questoes[{{ $index }}][fixa]" value="1" @checked(!empty($questao['fixa']))>
      <label class="form-check-label" for="questoes-{{ $index }}-fixa">Questão fixa</label>
      @if ($fixaErro)
      <div class="invalid-feedback d-block">{{ $errorsBag->first("questoes.$index.fixa") }}</div>
      @endif
    </div>
  </div>
</div>
