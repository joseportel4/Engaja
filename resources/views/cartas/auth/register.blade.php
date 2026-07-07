@extends('cartas.auth._shell')

@section('title', 'Criar conta - Cartas para Esperançar')

@section('auth-content')
    <h1 class="cartas-title">Crie sua conta</h1>

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.register.store') }}" class="cartas-form">
        @csrf
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="text" name="name" value="{{ old('name') }}" placeholder="Nome" required autofocus>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" required>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password" placeholder="Senha" required>
        </div>
        <button type="submit" class="cartas-button">Continuar</button>
    </form>

    <a href="{{ route('cartas.login') }}" class="cartas-link" style="margin-top:28px;font-weight:700;">Já tenho uma conta</a>
@endsection
