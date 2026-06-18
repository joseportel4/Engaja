@csrf

<div class="row g-3">
  <div class="col-md-6">
    <label for="nome" class="form-label">Nome <span class="text-danger">*</span></label>
    <input type="text" id="nome" name="nome"
           class="form-control @error('nome') is-invalid @enderror"
           value="{{ old('nome', $participante->nome ?? '') }}" required>
    @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="cpf" class="form-label">CPF</label>
    <input type="text" id="cpf" name="cpf"
           class="form-control @error('cpf') is-invalid @enderror"
           value="{{ old('cpf', $participante->cpf ?? '') }}"
           inputmode="numeric"
           maxlength="14"
           placeholder="000.000.000-00">
    @error('cpf') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="email" class="form-label">E-mail</label>
    <input type="email" id="email" name="email"
           class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $participante->email ?? '') }}">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="data_nascimento" class="form-label">Data de nascimento</label>
    <input type="date" id="data_nascimento" name="data_nascimento"
           class="form-control @error('data_nascimento') is-invalid @enderror"
           value="{{ old('data_nascimento', isset($participante?->data_nascimento) ? \Illuminate\Support\Carbon::parse($participante->data_nascimento)->format('Y-m-d') : '') }}">
    @error('data_nascimento') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="telefone" class="form-label">Telefone</label>
    <input type="text" id="telefone" name="telefone"
           class="form-control @error('telefone') is-invalid @enderror"
           value="{{ old('telefone', $participante->telefone ?? '') }}"
           inputmode="numeric"
           maxlength="15"
           placeholder="(00) 00000-0000">
    @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="vinculo" class="form-label">Vínculo</label>
    <select id="vinculo" name="vinculo" class="form-select @error('vinculo') is-invalid @enderror">
      <option value="">Selecione...</option>
      <option value="Rede Municipal" @selected(old('vinculo', $participante->vinculo ?? '') === 'Rede Municipal')>Rede Municipal</option>
      <option value="Movimentos Sociais" @selected(old('vinculo', $participante->vinculo ?? '') === 'Movimentos Sociais')>Movimentos Sociais</option>
    </select>
    @error('vinculo') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label for="turma_info" class="form-label">Turma do agendamento</label>
    <input type="text" id="turma_info" class="form-control"
           value="{{ $agendamento->turma ?: 'Sem turma definida no agendamento' }}" readonly disabled>
  </div>
</div>

<div class="d-flex justify-content-between mt-4">
  <a href="{{ route('agendamentos.participantes.index', $agendamento) }}" class="btn btn-outline-secondary">Cancelar</a>
  <button type="submit" class="btn btn-engaja">{{ $submitLabel ?? 'Salvar' }}</button>
</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const cpfInput = document.getElementById('cpf');
    const telefoneInput = document.getElementById('telefone');

    const applyCpfMask = (value) => {
      const digits = (value || '').replace(/\D/g, '').slice(0, 11);
      return digits
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1-$2');
    };

    const applyTelefoneMask = (value) => {
      const digits = (value || '').replace(/\D/g, '').slice(0, 11);
      return digits
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d)(\d{4})$/, '$1-$2');
    };

    if (cpfInput) {
      cpfInput.addEventListener('input', function () {
        this.value = applyCpfMask(this.value);
      });
      cpfInput.value = applyCpfMask(cpfInput.value);
    }

    if (telefoneInput) {
      telefoneInput.addEventListener('input', function () {
        this.value = applyTelefoneMask(this.value);
      });
      telefoneInput.value = applyTelefoneMask(telefoneInput.value);
    }
  });
</script>
@endpush