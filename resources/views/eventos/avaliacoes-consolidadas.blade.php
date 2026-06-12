@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <p class="text-uppercase small text-muted mb-1">Ação pedagógica</p>
      <h1 class="h3 fw-bold mb-1">Consolidação de avaliações</h1>
      <p class="text-muted mb-0">{{ $evento->nome }}</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar</a>
    </div>
  </div>

  <form method="GET" class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Agrupar por</label>
          <select name="agrupamento" class="form-select">
            <option value="geral" @selected($agrupamento === 'geral')>Todos os municípios</option>
            <option value="regiao" @selected($agrupamento === 'regiao')>Região</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-engaja">Aplicar</button>
        </div>
      </div>
    </div>
  </form>

  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div>
          <h2 class="h6 fw-bold mb-2">Como as médias são calculadas</h2>
          <ul class="text-muted small mb-0">
            <li>Escala e Numérica: média simples dos valores informados.</li>
            <li>Sim/Não: mostra a proporção de "Sim" entre respostas válidas (sem média numérica).</li>
            <li>Resposta única e Múltipla escolha: mostra a opção mais citada (sem média numérica).</li>
            <li>Texto aberto: não possui média; exibimos a quantidade de respostas abertas.</li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  @if ($agrupamento === 'regiao' && ! empty($grupos))
    <div class="d-flex flex-wrap gap-2 mb-3">
      <span class="text-muted small">Regiões agrupadas:</span>
      @foreach ($grupos as $grupo)
        <span class="badge bg-secondary-subtle text-secondary">
          {{ $grupo['nome'] }}
        </span>
      @endforeach
    </div>
  @endif

  @if (empty($grupos))
    <div class="card shadow-sm">
      <div class="card-body text-muted text-center">Sem respostas para esta ação pedagógica.</div>
    </div>
  @endif

  @foreach ($grupos as $grupo)
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-light">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h2 class="h5 fw-bold mb-0">{{ $grupo['nome'] }}</h2>
          <span class="text-muted small">{{ count($grupo['templates']) }} modelo(s)</span>
        </div>
      </div>
      <div class="card-body">
        @forelse ($grupo['templates'] as $template)
          <div class="mb-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
              <div>
                <h3 class="h6 fw-bold mb-1">{{ $template['template_nome'] }}</h3>
                <div class="text-muted small">
                  Submissões: {{ number_format($template['submissoes'] ?? 0, 0, ',', '.') }}
                  · Respostas: {{ number_format($template['respostas'] ?? 0, 0, ',', '.') }}
                </div>
              </div>
            </div>

            @php
              $tipoLabels = [
                'texto' => 'Texto aberto',
                'escala' => 'Escala',
                'numero' => 'Numérica',
                'boolean' => 'Sim/Não',
                'unica' => 'Resposta única',
                'multipla' => 'Múltipla escolha',
              ];
            @endphp
            <div class="table-responsive mt-3">
              <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Pergunta</th>
                    <th style="width: 140px;">Tipo</th>
                    <th>Resultado geral</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($template['perguntas'] as $pergunta)
                    <tr>
                      <td class="fw-semibold">{{ $pergunta['texto'] ?? 'Questão' }}</td>
                      <td>
                        <span class="badge bg-secondary-subtle text-secondary">
                          {{ $tipoLabels[$pergunta['tipo'] ?? 'texto'] ?? 'Texto aberto' }}
                        </span>
                      </td>
                      <td>{{ $pergunta['resumo'] ?? 'Sem respostas' }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        @empty
          <div class="text-muted">Sem modelos com respostas.</div>
        @endforelse
      </div>
    </div>
  @endforeach
</div>
@endsection
