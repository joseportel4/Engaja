@extends('cartas.layouts.app')

@section('title', 'Gerenciar usuários - Cartas para Esperançar')

@section('body')
    @include('cartas.shared._styles')

    <main class="cpe-page cpe-users-page">
        @include('cartas.shared._logo')

        <section class="cpe-users-shell">
            <div class="cpe-users-header">
                <div>
                    <h1 class="cpe-title">Gerenciar usuários</h1>
                    <p>Altere dados básicos e o perfil de acesso dos usuários do Cartas.</p>
                </div>

                <form method="GET" action="{{ route('cartas.usuarios.index') }}" class="cpe-search">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Pesquisar">
                    <button type="submit" aria-label="Pesquisar">⌕</button>
                </form>
            </div>

            @if (session('status'))
                <div class="cpe-alert">{{ session('status') }}</div>
            @endif

            <div class="cpe-table-card">
                <table class="cpe-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Perfil</th>
                            <th>Verificado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            @php($roleName = $user->roles->pluck('name')->first(fn ($name) => array_key_exists($name, $roles)))
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    <span class="cpe-pill {{ $roleName === 'cartas_admin' ? 'cpe-pill--blue' : ($roleName === 'cartas_gestao' ? 'cpe-pill--yellow' : 'cpe-pill--green') }}">
                                        {{ $roles[$roleName] ?? 'Sem perfil' }}
                                    </span>
                                </td>
                                <td>{{ $user->email_verified_at ? 'Sim' : 'Não' }}</td>
                                <td><a href="{{ route('cartas.usuarios.edit', $user) }}" class="cpe-link">Editar</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="cpe-empty">Nenhum usuário encontrado.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="cpe-users-footer">
                <a href="{{ route('cartas.dashboard') }}" class="cpe-button cpe-button--ghost">Voltar</a>
                <div>{{ $users->links() }}</div>
            </div>
        </section>

        @include('cartas.shared._user-menu')
    </main>

    @include('cartas.shared._scripts')

    <style>
        .cpe-users-page {
            padding: 0 28px 72px;
        }

        .cpe-users-shell {
            width: min(100%, 920px);
            margin: 120px auto 0;
            display: grid;
            gap: 18px;
        }

        .cpe-users-header {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 24px;
        }

        .cpe-users-header p {
            margin: 10px 0 0;
            color: #666;
            font-size: 14px;
        }

        .cpe-search {
            width: min(260px, 100%);
            height: 38px;
            border: 1px solid #ddd;
            border-radius: 999px;
            background: #fff;
            display: flex;
            align-items: center;
            padding: 0 10px 0 14px;
        }

        .cpe-search input {
            flex: 1;
            border: 0;
            outline: 0;
            font-size: 14px;
            background: transparent;
        }

        .cpe-search button {
            border: 0;
            background: transparent;
            font-size: 18px;
        }

        .cpe-empty {
            text-align: center;
            padding: 42px !important;
        }

        .cpe-users-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
        }

        @media (max-width: 760px) {
            .cpe-users-shell {
                margin-top: 64px;
            }

            .cpe-users-header,
            .cpe-users-footer {
                align-items: stretch;
                flex-direction: column;
            }
        }
    </style>
@endsection
