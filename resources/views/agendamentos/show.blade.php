@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">Agendamento</h1>
      <div class="d-flex gap-2">
        <a href="{{ route('agendamentos.participantes.index', $agendamento) }}" class="btn btn-outline-dark">Participantes</a>
        <a href="{{ route('agendamentos.edit', $agendamento) }}" class="btn btn-outline-secondary">Editar</a>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Data e horário</dt>
          <dd class="col-sm-8">{{ optional($agendamento->data_horario)->format('d/m/Y H:i') }}</dd>

          <dt class="col-sm-4">Município</dt>
          <dd class="col-sm-8">{{ $agendamento->municipio?->nome_com_estado ?? '—' }}</dd>

          <dt class="col-sm-4">Atividade/Ação</dt>
          <dd class="col-sm-8">{{ $agendamento->atividadeAcao?->nome ?? '—' }}</dd>

          <dt class="col-sm-4">Turma</dt>
          <dd class="col-sm-8">{{ $agendamento->turma ?: '—' }}</dd>

          <dt class="col-sm-4">Público participante</dt>
          <dd class="col-sm-8">{{ $agendamento->publico_participante }}</dd>

          <dt class="col-sm-4">Local da ação</dt>
          <dd class="col-sm-8">{{ $agendamento->local_acao }}</dd>

          <dt class="col-sm-4">Registrado por</dt>
          <dd class="col-sm-8">{{ $agendamento->user?->name ?? '—' }}</dd>

          <dt class="col-sm-4">Participantes cadastrados</dt>
          <dd class="col-sm-8">{{ $agendamento->participantes_clonados_count ?? 0 }}</dd>

          <dt class="col-sm-4">Detalhe da atividade/ação</dt>
          <dd class="col-sm-8">{{ $agendamento->atividadeAcao?->detalhe ?: '—' }}</dd>
        </dl>
      </div>
    </div>

    <a href="{{ route('agendamentos.index') }}" class="btn btn-link px-0 mt-3">Voltar para lista</a>
  </div>
</div>
@endsection