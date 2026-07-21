@extends('cartas.auth._shell')

@section('title', 'Verifique seu e-mail - Cartas para Esperançar')
@section('auth-bg-style', "background-image: url('" . asset('images/cartas/bg-recuperar-senha.png') . "');")

@section('auth-content')
    <h1 class="cartas-title cartas-title--strong">Verifique seu e-mail</h1>
    <p class="cartas-copy">Enviamos uma mensagem de confirmação para seu e-mail. Abra a mensagem e clique no link para validar sua conta.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="cartas-alert">Um novo link de confirmação foi enviado para o e-mail informado no cadastro.</div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" class="cartas-form">
        @csrf
        <button type="submit" class="cartas-button">Reenviar e-mail de confirmação</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;">
        @csrf
        <button type="submit" class="cartas-link" style="background:none;border:none;cursor:pointer;font:inherit;">Sair</button>
    </form>
@endsection
