@extends('layouts.app')

@section('content')
@php
  $isUniversal = $avaliacao->atividade_id === null;
  $respondente = $isUniversal
      ? 'resposta anônima'
      : (optional($submissao->presenca->inscricao->participante->user)->name ?? 'N/A');
@endphp
<div class="row justify-content-center">
  <div class="col-xl-9">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 fw-bold text-engaja mb-0">Respostas de {{ $respondente }}</h1>
      <a href="{{ route('avaliacoes.respostas', $avaliacao) }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <p class="mb-3">
          <strong>Enviado em:</strong> {{ $submissao->created_at->format('d/m/Y H:i') }}<br>
          @if($isUniversal)
          <strong>Avaliação universal:</strong> {{ $avaliacao->descricao_universal ?: 'Sem descrição' }}
          @else
          <strong>Atividade:</strong> {{ $avaliacao->atividade->descricao ?? 'N/A' }} — {{ $avaliacao->atividade->evento->nome ?? 'N/A' }}
          @endif
        </p>

        <div class="list-group">
          @foreach($avaliacao->avaliacaoQuestoes as $questao)
            @php
              $resp = $respostasPorQuestao->get($questao->id);
            @endphp
            <div class="list-group-item">
              <div class="fw-semibold mb-1">{{ $questao->texto }}</div>
              <div class="text-muted small mb-2">
                Tipo: {{ $questao->tipo }} |
                Evidência: {{ $questao->evidencia->descricao ?? '—' }} |
                Escala: {{ $questao->escala->descricao ?? '—' }}
              </div>
              <div>
                @if($resp)
                  {!! nl2br(e($resp->resposta)) !!}
                @else
                  <span class="text-muted">Não respondida.</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
