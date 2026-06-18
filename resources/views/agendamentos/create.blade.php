@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-6">
    <h1 class="h3 fw-bold text-engaja mb-4">Novo agendamento</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('agendamentos.store') }}">
          @include('agendamentos._form', ['submitLabel' => 'Criar agendamento'])
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

