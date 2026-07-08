@extends('cartas.auth._shell')

@section('title', 'Entrar - Cartas para Esperançar')

@section('auth-content')
    <h1 class="cartas-title">Entrar</h1>

    @if (session('status'))
        <div class="cartas-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.login.store') }}" class="cartas-form">
        @csrf
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="Email" required autofocus>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password" placeholder="Senha" required>
        </div>

        <button type="submit" class="cartas-button">Entrar</button>
    </form>

    <div class="cartas-links">
        <a href="{{ route('cartas.register') }}" class="cartas-link">Criar conta</a>
        <a href="{{ route('cartas.password.request') }}" class="cartas-link">Esqueci minha senha</a>
    </div>
@endsection
