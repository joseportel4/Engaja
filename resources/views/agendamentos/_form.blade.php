@csrf

@php
  $dataHorarioValor = old('data_horario');

  if ($dataHorarioValor === null && isset($agendamento) && $agendamento->data_horario) {
      $dataHorarioValor = $agendamento->data_horario->format('Y-m-d\\TH:i');
  }

  $turmaSelecionada = old('turma', $agendamento->turma ?? '');
@endphp

<div class="mb-3">
  <label for="data_horario" class="form-label">Data e horário <span class="text-danger">*</span></label>
  <input type="datetime-local" id="data_horario" name="data_horario"
         class="form-control @error('data_horario') is-invalid @enderror"
         value="{{ $dataHorarioValor }}" required>
  @error('data_horario')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label d-block">Atividade/Ação <span class="text-danger">*</span></label>
  @forelse($atividadeAcoes as $atividadeAcao)
    @php
      $turmasDaAcao = $atividadeAcao->turmas_configuradas;
      $temTurmas = $atividadeAcao->usa_turmas && count($turmasDaAcao) > 0;
    @endphp
    <div class="form-check border rounded px-3 py-2 mb-2">
      <input class="form-check-input mt-1 @error('atividade_acao_id') is-invalid @enderror"
             type="radio"
             name="atividade_acao_id"
             id="atividade_acao_{{ $atividadeAcao->id }}"
             value="{{ $atividadeAcao->id }}"
             data-usa-turmas="{{ $temTurmas ? '1' : '0' }}"
             data-turmas='@json($turmasDaAcao)'
             @checked((string) old('atividade_acao_id', $agendamento->atividade_acao_id ?? '') === (string) $atividadeAcao->id)
             required>
      <label class="form-check-label w-100" for="atividade_acao_{{ $atividadeAcao->id }}">
        <strong>{{ $atividadeAcao->nome }}</strong>
        @if($temTurmas)
          <small class="d-block text-muted">Turmas disponíveis: {{ implode(', ', $turmasDaAcao) }}</small>
        @endif
        <small class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($atividadeAcao->detalhe), 120) ?: 'Sem detalhe' }}</small>
      </label>
    </div>
  @empty
    <div class="alert alert-warning mb-0">
      Nenhuma atividade/ação cadastrada. Cadastre primeiro em <a href="{{ route('atividade-acoes.create') }}">Atividade/Ação</a>.
    </div>
  @endforelse

  @error('atividade_acao_id')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3" id="turma_section" style="display:none;">
  <label class="form-label d-block">Turma <span class="text-danger">*</span></label>
  <div id="turma_radios" class="vstack gap-2"></div>
  @error('turma')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="publico_participante" class="form-label">Público participante <span class="text-danger">*</span></label>
  <input type="text" id="publico_participante" name="publico_participante"
         class="form-control @error('publico_participante') is-invalid @enderror"
         value="{{ old('publico_participante', $agendamento->publico_participante ?? '') }}"
         placeholder="Ex.: Professores da rede municipal" required>
  @error('publico_participante')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="local_acao" class="form-label">Local da ação <span class="text-danger">*</span></label>
  <input type="text" id="local_acao" name="local_acao"
         class="form-control @error('local_acao') is-invalid @enderror"
         value="{{ old('local_acao', $agendamento->local_acao ?? '') }}"
         placeholder="Ex.: Escola Municipal X" required>
  @error('local_acao')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="d-flex justify-content-between">
  <a href="{{ route('agendamentos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
  <button type="submit" class="btn btn-engaja" @disabled($atividadeAcoes->isEmpty())>{{ $submitLabel ?? 'Salvar' }}</button>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const atividadeRadios = Array.from(document.querySelectorAll('input[name="atividade_acao_id"]'));
    const turmaSection = document.getElementById('turma_section');
    const turmaRadios = document.getElementById('turma_radios');
    const turmaSelecionada = @json($turmaSelecionada);

    const normalizeId = (value) => String(value)
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');

    const parseTurmas = (raw) => {
      try {
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    };

    const renderTurmas = (selectedRadio) => {
      if (!selectedRadio) {
        turmaSection.style.display = 'none';
        turmaRadios.innerHTML = '';
        return;
      }

      const usaTurmas = selectedRadio.dataset.usaTurmas === '1';
      const turmas = parseTurmas(selectedRadio.dataset.turmas);

      if (!usaTurmas || turmas.length === 0) {
        turmaSection.style.display = 'none';
        turmaRadios.innerHTML = '';
        return;
      }

      turmaSection.style.display = 'block';
      turmaRadios.innerHTML = '';

      turmas.forEach((turma) => {
        const id = `turma_${selectedRadio.value}_${normalizeId(turma)}`;
        const checked = String(turmaSelecionada) === String(turma) ? 'checked' : '';

        const wrapper = document.createElement('div');
        wrapper.className = 'form-check border rounded px-3 py-2';
        wrapper.innerHTML = `
          <input class="form-check-input" type="radio" name="turma" id="${id}" value="${turma}" ${checked} required>
          <label class="form-check-label" for="${id}">${turma}</label>
        `;
        turmaRadios.appendChild(wrapper);
      });
    };

    atividadeRadios.forEach((radio) => {
      radio.addEventListener('change', function () {
        renderTurmas(this);
      });
    });

    const initialSelected = atividadeRadios.find((radio) => radio.checked);
    renderTurmas(initialSelected);
  });
</script>
@endpush