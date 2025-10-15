@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">Questão</h1>
      <a href="{{ route('questaos.edit', $questao) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 fw-semibold">Enunciado</h2>
        <p class="mb-4">{{ $questao->texto }}</p>

        <div class="row mb-3">
          <div class="col-md-4">
            <span class="fw-semibold d-block text-muted text-uppercase small">Tipo</span>
            <span>{{ ucfirst($questao->tipo) }}</span>
          </div>
          <div class="col-md-4">
            <span class="fw-semibold d-block text-muted text-uppercase small">Indicador</span>
            <span>{{ $questao->indicador->descricao ?? '—' }}</span>
          </div>
          <div class="col-md-4">
            <span class="fw-semibold d-block text-muted text-uppercase small">Dimensão</span>
            <span>{{ $questao->indicador->dimensao->descricao ?? '—' }}</span>
          </div>
        </div>

        <div class="mb-3">
          <span class="fw-semibold d-block text-muted text-uppercase small">Escala</span>
          @if ($questao->escala)
          <p class="mb-1">{{ $questao->escala->descricao }}</p>
          <ul class="list-inline">
            @php
              $opcoes = collect([$questao->escala->opcao1, $questao->escala->opcao2, $questao->escala->opcao3, $questao->escala->opcao4, $questao->escala->opcao5])->filter();
            @endphp
            @foreach ($opcoes as $opcao)
            <li class="list-inline-item badge bg-light text-dark border">{{ $opcao }}</li>
            @endforeach
          </ul>
          @else
          <span class="text-muted">—</span>
          @endif
        </div>

        <div class="mb-3">
          <span class="fw-semibold d-block text-muted text-uppercase small">Questão fixa?</span>
          <span>{{ $questao->fixa ? 'Sim' : 'Não' }}</span>
        </div>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-3">Templates que utilizam esta questão</h2>
        <ul class="list-group list-group-flush">
          @forelse ($questao->templates as $template)
          <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
            <span>{{ $template->nome }}</span>
            <a href="{{ route('templates-avaliacao.show', $template) }}" class="btn btn-sm btn-outline-primary">Ver template</a>
          </li>
          @empty
          <li class="list-group-item px-0 text-muted">Nenhum template associado.</li>
          @endforelse
        </ul>
      </div>
    </div>

    <a href="{{ route('questaos.index') }}" class="btn btn-link px-0 mt-3">Voltar para lista</a>
  </div>
</div>
@endsection
