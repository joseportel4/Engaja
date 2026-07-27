@extends('layouts.app')

@section('content')
<div class="container">
  @php $disableImport = $atividades->isEmpty(); @endphp
  {{-- Cabeçalho --}}
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h1 class="h4 fw-bold text-engaja mb-1">Importar inscrições</h1>
      <div class="text-muted small">
        Ação pedagógica: <strong>{{ $evento->nome }}</strong>
        @php
          $periodoInicio = $evento->data_inicio ? \Carbon\Carbon::parse($evento->data_inicio)->format('d/m/Y') : null;
          $periodoFim = $evento->data_fim ? \Carbon\Carbon::parse($evento->data_fim)->format('d/m/Y') : null;
        @endphp
        @php $mostrarPeriodoFim = $periodoFim && (!$periodoInicio || $periodoFim !== $periodoInicio); @endphp
        @if($periodoInicio || $periodoFim)
        • {{ $periodoInicio ?? '—' }} @if($mostrarPeriodoFim)<span class="text-muted">até {{ $periodoFim }}</span>@endif
        @endif
      </div>
    </div>

    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">
      Voltar à ação pedagógica
    </a>
  </div>

  @if ($errors->any())
  <div class="alert alert-danger">
    <strong>Ops!</strong> Verifique o arquivo e tente novamente.
  </div>
  @endif

  {{-- Card do Formulário --}}
  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST"
        action="{{ route('inscricoes.cadastro', $evento) }}"
        enctype="multipart/form-data"
        class="row g-3">
        @csrf

        <div class="col-12">
          <label class="form-label">Momento <span class="text-danger">*</span></label>
          @if($disableImport)
          <div class="alert alert-warning mb-2">
            Nenhum momento cadastrado para este evento. Cadastre um momento antes de importar as inscrições.
          </div>
          <select class="form-select" disabled>
            <option>Cadastre um momento antes de prosseguir</option>
          </select>
          @else
          <select
            name="atividade_id"
            class="form-select @error('atividade_id') is-invalid @enderror">
            <option value="" @selected(old('atividade_id', '') === '')>Todos os momentos desta ação</option>
            @foreach($atividades as $at)
            @php
              $dia = \Carbon\Carbon::parse($at->dia)->format('d/m/Y');
              $hora = $at->hora_inicio ? \Carbon\Carbon::parse($at->hora_inicio)->format('H:i') : null;
              $label = trim(($at->descricao ?: 'Momento') . ' — ' . $dia . ($hora ? ' ' . $hora : ''));
            @endphp
            <option value="{{ $at->id }}" @selected(old('atividade_id') == $at->id)>{{ $label }}</option>
            @endforeach
          </select>
          @error('atividade_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <div class="form-text">
            Em <strong>todos os momentos</strong>, cada participante da planilha será inscrito em cada momento da ação (o mesmo comportamento de “Inscrever selecionados” com o filtro em todos os momentos).
          </div>
          @endif
        </div>

        <div class="col-12">
          <label class="form-label">Origem da importação</label>
          <input type="text"
            name="origem"
            class="form-control @error('origem') is-invalid @enderror"
            value="{{ old('origem') }}"
            maxlength="255"
            placeholder="Ex.: LP, Moodle, formulário externo"
            @if($disableImport) disabled @endif>
          @error('origem') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <div class="form-text">
            Se informada, essa origem será gravada para todos os usuários da planilha nesta ação.
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Arquivo Excel ou CSV (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
          <input type="file"
            name="your_file"
            class="form-control @error('your_file') is-invalid @enderror"
            accept=".xlsx,.xls,.csv"
            @if($disableImport) disabled @endif
            required>
          @error('your_file') <div class="invalid-feedback">{{ $message }}</div> @enderror
          <div class="form-text">
            Envie um arquivo Excel ou CSV com a primeira linha como cabeçalho.
          </div>
        </div>
        <div class="mb-3">
          <div class="form-text">
            Colunas: nome, email, cpf, telefone, municipio, estado/uf (opcional), tipo_de_organizacao, organizacao, tag
            <br>
            Municípios ainda não cadastrados serão consultados no IBGE e criados automaticamente. Informe o estado/UF quando houver cidades com o mesmo nome.
          </div>
          <div class="mt-2">
            <a href="{{ asset('modelos/modelo_inscricoes_engaja.xlsx') }}" class="btn btn-sm btn-outline-primary">
              📥 Baixar modelo de planilha
            </a>
          </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Cancelar</a>
          <button type="submit" class="btn btn-engaja" @if($disableImport) disabled @endif>
            Importar
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- (Opcional) Link para baixar um modelo .xlsx --}}
  <div class="mt-3">
    {{-- Se criar uma rota para template, troque abaixo: --}}
    {{-- <a href="{{ route('inscricoes.template') }}" class="link-secondary small">Baixar modelo (.xlsx)</a> --}}
  </div>
</div>
@endsection
