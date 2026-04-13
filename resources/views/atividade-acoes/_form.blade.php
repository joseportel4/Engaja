@csrf

@php
  $usaTurmasOld = old('usa_turmas');
  $usaTurmas = $usaTurmasOld !== null
      ? filter_var($usaTurmasOld, FILTER_VALIDATE_BOOLEAN)
      : (bool) ($atividadeAcao->usa_turmas ?? false);

  $turmas = old('turmas', $atividadeAcao->turmas_configuradas ?? []);
  if (!is_array($turmas)) {
      $turmas = [];
  }
  $turmas = collect($turmas)
      ->map(fn($turma) => trim((string) $turma))
      ->filter()
      ->values()
      ->all();

  if (empty($turmas)) {
      $turmas = [''];
  }
@endphp

<div class="mb-3">
  <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
  <input type="text" id="nome" name="nome"
         class="form-control @error('nome') is-invalid @enderror"
         value="{{ old('nome', $atividadeAcao->nome ?? '') }}" required>
  @error('nome')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="detalhe" class="form-label">Detalhe</label>
  <textarea id="detalhe" name="detalhe" rows="4"
            class="form-control @error('detalhe') is-invalid @enderror"
            placeholder="Detalhes da atividade/ação">{{ old('detalhe', $atividadeAcao->detalhe ?? '') }}</textarea>
  @error('detalhe')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3 form-check">
  <input type="hidden" name="usa_turmas" value="0">
  <input class="form-check-input" type="checkbox" value="1" id="usa_turmas" name="usa_turmas" @checked($usaTurmas)>
  <label class="form-check-label" for="usa_turmas">
    Esta atividade/ação possui turmas
  </label>
</div>

<div class="mb-4" id="turmas_section" style="display: {{ $usaTurmas ? 'block' : 'none' }};">
  <label class="form-label">Turmas <span class="text-danger" id="turmas_required_mark" style="display: {{ $usaTurmas ? 'inline' : 'none' }};">*</span></label>
  <div id="turmas_list" class="vstack gap-2">
    @foreach($turmas as $turma)
      <div class="input-group turma-item">
        <input type="text" name="turmas[]" value="{{ $turma }}" class="form-control" placeholder="Ex.: Turma A">
        <button type="button" class="btn btn-outline-danger js-remove-turma">Remover</button>
      </div>
    @endforeach
  </div>
  <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add_turma_btn">+ Adicionar turma</button>
  <div class="form-text">Você pode definir o nome e a quantidade de turmas livremente.</div>
  @error('turmas')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
  @error('turmas.*')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

<div class="d-flex justify-content-between">
  <a href="{{ route('atividade-acoes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  <button type="submit" class="btn btn-engaja">{{ $submitLabel ?? 'Salvar' }}</button>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const usaTurmasCheckbox = document.getElementById('usa_turmas');
    const turmasSection = document.getElementById('turmas_section');
    const turmasList = document.getElementById('turmas_list');
    const addTurmaBtn = document.getElementById('add_turma_btn');
    const mark = document.getElementById('turmas_required_mark');

    const buildTurmaItem = (valor = '') => {
      const wrapper = document.createElement('div');
      wrapper.className = 'input-group turma-item';
      wrapper.innerHTML = `
        <input type="text" name="turmas[]" class="form-control" placeholder="Ex.: Turma A" value="${valor}">
        <button type="button" class="btn btn-outline-danger js-remove-turma">Remover</button>
      `;
      return wrapper;
    };

    const ensureAtLeastOne = () => {
      const items = turmasList.querySelectorAll('.turma-item');
      if (items.length === 0) {
        turmasList.appendChild(buildTurmaItem());
      }
    };

    const syncVisibility = () => {
      const enabled = usaTurmasCheckbox.checked;
      turmasSection.style.display = enabled ? 'block' : 'none';
      mark.style.display = enabled ? 'inline' : 'none';
      if (enabled) {
        ensureAtLeastOne();
      }
    };

    addTurmaBtn?.addEventListener('click', function () {
      turmasList.appendChild(buildTurmaItem());
    });

    turmasList?.addEventListener('click', function (event) {
      if (!event.target.classList.contains('js-remove-turma')) return;
      event.target.closest('.turma-item')?.remove();
      if (usaTurmasCheckbox.checked) {
        ensureAtLeastOne();
      }
    });

    usaTurmasCheckbox?.addEventListener('change', syncVisibility);
    syncVisibility();
  });
</script>
@endpush

