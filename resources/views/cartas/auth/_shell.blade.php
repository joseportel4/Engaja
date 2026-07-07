@extends('cartas.layouts.app')

@section('body')
    <main class="cartas-auth-shell">
        <aside class="cartas-auth-side" aria-hidden="true"></aside>
        <section class="cartas-auth-main">
            <div class="cartas-auth-content">
                <img
                    src="{{ asset('images/cartas/cartas-logo.png') }}"
                    alt="Cartas para Esperançar"
                    class="cartas-logo @yield('logoClass')"
                >
                @yield('auth-content')
            </div>
        </section>
    </main>
@endsection
