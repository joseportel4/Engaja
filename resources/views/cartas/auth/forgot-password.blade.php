@extends('cartas.auth._shell')

@section('title', 'Recuperar senha - Cartas para Esperançar')
@section('auth-bg-style', "background-image: url('" . asset('images/cartas/bg-cadastro.png') . "');")

@section('auth-content')
    <h1 class="cartas-title">Recuperar senha</h1>

    @if (session('status'))
        <div class="cartas-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.password.email') }}" class="cartas-form">
        @csrf
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" required autofocus>
        </div>
        <button type="submit" class="cartas-button">Enviar</button>
    </form>

    <a href="{{ route('cartas.login') }}" class="cartas-link" style="margin-top:28px;font-weight:700;">Voltar para o login</a>
@endsection
