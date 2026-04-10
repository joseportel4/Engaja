@extends('layouts.app')

@section('content')
<div class="container py-4" id="avaliacoes-dashboard" data-endpoint="{{ route('dashboards.avaliacoes.data') }}">
  <div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <p class="text-uppercase small text-muted mb-1">Dashboards</p>
        <h1 class="h3 fw-bold mb-1">Respostas dos formularios</h1>
        <p class="text-muted mb-0">Visual limpo, na paleta do projeto, com filtros instantaneos.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hub de dashboards</a>
        <a href="{{ route('dashboards.bi') }}" class="btn btn-outline-secondary">Ir para BI</a>
        <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-primary">Ir para presencas</a>
      </div>
    </div>
  </div>

  <div class="card shadow-sm border-0 mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Modelo</label>
          <select class="form-select js-filter" id="f-template">
            <option value="">Todos</option>
            @foreach($templates as $template)
            <option value="{{ $template->id }}" @selected(request('template_id') == $template->id)>{{ $template->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Evento</label>
          <select class="form-select js-filter" id="f-evento">
            <option value="">Todos</option>
            @foreach($eventos as $evento)
            <option value="{{ $evento->id }}">{{ $evento->nome }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <label class="form-label text-muted small mb-1">Atividade / momento</label>
          <select class="form-select js-filter" id="f-atividade">
            <option value="">Todas</option>
            @foreach($atividades as $atividade)
            @php
              $diaFormatado = $atividade->dia ? \Illuminate\Support\Carbon::parse($atividade->dia)->format('d/m') : '';
            @endphp
            <option value="{{ $atividade->id }}">
              {{ $atividade->descricao ?? 'Momento' }} - {{ $diaFormatado }} {{ $atividade->hora_inicio }}
              @if($atividade->evento) ({{ $atividade->evento->nome }}) @endif
            </option>
            @endforeach
          </select>
        </div>
        <div class="col-lg-3 col-md-6">
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">De</label>
              <input type="date" class="form-control js-filter" id="f-de">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Ate</label>
              <input type="date" class="form-control js-filter" id="f-ate">
            </div>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between align-items-center mt-3">
        <button class="btn btn-primary" id="btn-recarregar">
          Atualizar agora
        </button>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3" id="cards-totais">
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Submissoes</p>
          <div class="h3 fw-bold mb-0" data-total="submissoes">0</div>
          <small class="text-muted">Respostas completas registradas</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Questoes</p>
          <div class="h3 fw-bold mb-0" data-total="questoes">0</div>
          <small class="text-muted">Com alguma resposta</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Eventos</p>
          <div class="h3 fw-bold mb-0" data-total="eventos">0</div>
          <small class="text-muted">Com respostas vinculadas</small>
        </div>
      </div>
    </div>
    <div class="col-lg-3 col-sm-6">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-body">
          <p class="text-uppercase small text-muted mb-1">Ultima resposta</p>
          <div class="h3 fw-bold mb-0" data-total="ultima">-</div>
          <small class="text-muted">Horario da ultima entrada</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 fw-bold mb-0">Distribuicao por questao</h2>
        <span class="badge bg-primary-subtle text-primary">Interativo</span>
      </div>
      <div class="row g-3" id="cards-questoes">
        <div class="col-12" id="placeholder-card">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted">
              Carregando graficos...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="textAnswersModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title js-text-modal-title">Respostas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted small mb-2 js-text-modal-count"></div>
        <div class="vstack gap-2 js-text-modal-list" style="max-height: 60vh; overflow: auto;"></div>
      </div>
    </div>
  </div>
</div>

@endsection

@push('styles')
<style>
  @media (min-width: 768px) {
    #cards-questoes > .col-md-6:only-child,
    #cards-questoes > .col-md-6:nth-child(odd):nth-last-child(1) {
      flex: 0 0 100%;
      max-width: 100%;
    }
  }
  @media (max-width: 576px) {
    #cards-questoes .question-header,
    #cards-questoes-momento .question-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    #cards-questoes .question-controls,
    #cards-questoes-momento .question-controls {
      width: 100%;
      justify-content: flex-start;
    }
    #cards-questoes .question-controls select,
    #cards-questoes-momento .question-controls select {
      width: 100%;
      max-width: none;
    }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@vite(['resources/js/avaliacoes-distribuicao-charts.js'])
@endpush
