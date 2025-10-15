@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-8">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 fw-bold text-engaja mb-1">Avaliação</h1>
        <p class="text-muted mb-0">Registrada em {{ $avaliacao->created_at?->format('d/m/Y H:i') ?? '—' }}</p>
      </div>
      <a href="{{ route('avaliacoes.edit', $avaliacao) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-3">Contexto</h2>
        <dl class="row mb-0">
          <dt class="col-md-4 text-muted">Participante</dt>
          <dd class="col-md-8">{{ $avaliacao->inscricao->participante->user->name ?? '—' }}</dd>

          <dt class="col-md-4 text-muted">Evento</dt>
          <dd class="col-md-8">{{ $avaliacao->inscricao->evento->nome ?? '—' }}</dd>

          <dt class="col-md-4 text-muted">Atividade</dt>
          <dd class="col-md-8">{{ $avaliacao->atividade->descricao ?? '—' }}</dd>

          <dt class="col-md-4 text-muted">Template</dt>
          <dd class="col-md-8">{{ $avaliacao->templateAvaliacao->nome ?? '—' }}</dd>
        </dl>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-3">Respostas</h2>

        @php
          $respostas = $avaliacao->respostas->pluck('resposta', 'questao_id');
        @endphp

        <ol class="list-group list-group-numbered list-group-flush">
          @forelse ($avaliacao->templateAvaliacao->questoes as $questao)
          <li class="list-group-item px-0">
            <p class="fw-semibold mb-1">{{ $questao->texto }}</p>
            <p class="text-muted small mb-2">
              Indicador: {{ $questao->indicador->descricao ?? '—' }} • Dimensão: {{ $questao->indicador->dimensao->descricao ?? '—' }}
            </p>
            <p class="mb-0">
              @php $resposta = $respostas[$questao->id] ?? null; @endphp
              {{ $resposta !== null && $resposta !== '' ? $resposta : 'Sem resposta' }}
            </p>
          </li>
          @empty
          <li class="list-group-item px-0 text-muted">Nenhuma questão cadastrada para este template.</li>
          @endforelse
        </ol>
      </div>
    </div>

    <a href="{{ route('avaliacoes.index') }}" class="btn btn-link px-0 mt-3">Voltar para lista</a>
  </div>
</div>
@endsection
