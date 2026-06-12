@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-9">
    <h1 class="h4 fw-bold text-engaja mb-3">Editar participante do agendamento</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('agendamentos.participantes.update', [$agendamento, $participante]) }}">
          @method('PUT')
          @include('agendamentos.participantes._form', ['submitLabel' => 'Salvar alterações'])
        </form>
      </div>
    </div>
  </div>
</div>
@endsection