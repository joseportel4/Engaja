<div class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label" for="nome">Nome*</label>
        <input type="text" id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror"
          value="{{ old('nome', $modelo->nome ?? '') }}" required>
        @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="eixo_id">Eixo</label>
        <select id="eixo_id" name="eixo_id" class="form-select @error('eixo_id') is-invalid @enderror">
          <option value="">-- Nenhum --</option>
          @foreach($eixos as $id => $nomeEixo)
            <option value="{{ $id }}" @selected(old('eixo_id', $modelo->eixo_id ?? '') == $id)>{{ $nomeEixo }}</option>
          @endforeach
        </select>
        @error('eixo_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" rows="2" class="form-control @error('descricao') is-invalid @enderror">{{ old('descricao', $modelo->descricao ?? '') }}</textarea>
        @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="imagem_frente">Imagem da frente</label>
        <input type="file" id="imagem_frente" name="imagem_frente" accept="image/*"
          class="form-control @error('imagem_frente') is-invalid @enderror">
        <div class="mt-2" id="preview-frente-wrapper">
          @if(!empty($modelo?->imagem_frente))
            <img src="{{ asset('storage/'.$modelo->imagem_frente) }}" alt="Imagem atual da frente" class="img-fluid border rounded" style="max-height: 180px;">
          @endif
        </div>
        @error('imagem_frente') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-md-6">
        <label class="form-label" for="imagem_verso">Imagem do verso</label>
        <input type="file" id="imagem_verso" name="imagem_verso" accept="image/*"
          class="form-control @error('imagem_verso') is-invalid @enderror">
        <div class="mt-2" id="preview-verso-wrapper">
          @if(!empty($modelo?->imagem_verso))
            <img src="{{ asset('storage/'.$modelo->imagem_verso) }}" alt="Imagem atual do verso" class="img-fluid border rounded" style="max-height: 180px;">
          @endif
        </div>
        @error('imagem_verso') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="texto_frente">Texto da frente</label>
        <textarea id="texto_frente" name="texto_frente" rows="4" class="form-control @error('texto_frente') is-invalid @enderror">{{ old('texto_frente', $modelo->texto_frente ?? '') }}</textarea>
        @error('texto_frente') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>

      <div class="col-12">
        <label class="form-label" for="texto_verso">Texto do verso</label>
        <textarea id="texto_verso" name="texto_verso" rows="4" class="form-control @error('texto_verso') is-invalid @enderror">{{ old('texto_verso', $modelo->texto_verso ?? '') }}</textarea>
        @error('texto_verso') <div class="invalid-feedback">{{ $message }}</div> @enderror
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-end">
  <button type="submit" class="btn btn-engaja">Salvar modelo</button>
</div>

@push('scripts')
<script>
  function previewFile(inputId, previewWrapperId) {
    const input = document.getElementById(inputId);
    const wrap = document.getElementById(previewWrapperId);
    if (!input || !wrap) return;
    input.addEventListener('change', e => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const url = URL.createObjectURL(file);
      wrap.innerHTML = `<img src="${url}" alt="Pré-visualização" class="img-fluid border rounded" style="max-height: 180px;">`;
    });
  }
  previewFile('imagem_frente', 'preview-frente-wrapper');
  previewFile('imagem_verso', 'preview-verso-wrapper');
</script>
@endpush
