@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-10">
    <h1 class="h3 fw-bold text-engaja mb-4">Nova avaliação</h1>

    @php
      $selectedTemplateId = old('template_avaliacao_id', $templates->first()->id ?? null);
    @endphp

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form method="POST" action="{{ route('avaliacoes.store') }}">
          @csrf

          <div class="row g-3">
            <div class="col-md-4">
              <label for="inscricao_id" class="form-label">Inscrição</label>
              <select id="inscricao_id" name="inscricao_id"
                class="form-select @error('inscricao_id') is-invalid @enderror" required>
                <option value="">Selecione...</option>
                @foreach ($inscricoes as $inscricao)
                <option value="{{ $inscricao->id }}" @selected(old('inscricao_id') == $inscricao->id)>
                  {{ $inscricao->participante->user->name ?? 'Participante sem nome' }} —
                  {{ $inscricao->evento->nome ?? 'Evento indefinido' }}
                </option>
                @endforeach
              </select>
              @error('inscricao_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label for="atividade_id" class="form-label">Atividade</label>
              <select id="atividade_id" name="atividade_id"
                class="form-select @error('atividade_id') is-invalid @enderror" required>
                <option value="">Selecione...</option>
                @foreach ($atividades as $atividade)
                <option value="{{ $atividade->id }}" @selected(old('atividade_id') == $atividade->id)>
                  {{ $atividade->descricao }} — {{ $atividade->evento->nome ?? 'Sem evento' }}
                </option>
                @endforeach
              </select>
              @error('atividade_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="col-md-4">
              <label for="template_avaliacao_id" class="form-label">Modelo de avaliação</label>
              <select id="template_avaliacao_id" name="template_avaliacao_id"
                class="form-select @error('template_avaliacao_id') is-invalid @enderror" required>
                <option value="">Selecione...</option>
                @foreach ($templates as $template)
                <option value="{{ $template->id }}" @selected($selectedTemplateId == $template->id)>
                  {{ $template->nome }}
                </option>
                @endforeach
              </select>
              @error('template_avaliacao_id')
              <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="mt-4">
            @include('avaliacoes._questoes', [
                'templates' => $templates,
                'selectedTemplateId' => $selectedTemplateId,
                'respostasAntigas' => old('respostas', []),
            ])
          </div>

          <div class="d-flex justify-content-between mt-4">
            <a href="{{ route('avaliacoes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-engaja">Salvar avaliação</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
