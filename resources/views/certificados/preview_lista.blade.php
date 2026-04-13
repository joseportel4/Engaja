@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h1 class="h4 fw-bold text-engaja mb-1">Pré-visualização da Certificação</h1>
        <p class="text-muted">Confira abaixo todos os usuários que receberão o certificado antes de confirmar a emissão.</p>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">Modelo de Certificado Selecionado:</h6>
            <p class="text-muted mb-0">{{ $modelo->nome }}</p>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>CPF</th>
                        <th>Ação Pedagógica</th>
                        <th>Carga Horária no Certificado</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($paginator as $row)
                    <tr>
                        <td class="fw-medium">{{ $row['nome'] }}</td>
                        <td>{{ $row['email'] }}</td>
                        <td>{{ $row['cpf'] }}</td>
                        <td>{{ $row['evento_nome'] }}</td>
                        <td><span class="badge bg-secondary-subtle text-secondary border">{{ $row['carga_horaria'] }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Nenhum participante apto para certificação nestas ações.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer bg-white p-3">
            <div class="row align-items-center">

                <div class="col-12 col-xl-4 text-center text-xl-start mb-3 mb-xl-0">
                    <div class="text-muted small">
                        <strong>{{ $totalProntos }}</strong> certificados serão emitidos.
                        @if($skippedZeroWorkload > 0)
                        <br><span class="text-warning-emphasis">⚠️ {{ $skippedZeroWorkload }} ignorados (Carga horária zero).</span>
                        @endif
                    </div>
                </div>

                {{-- paginação --}}
                <div class="col-12 col-xl-4 d-flex justify-content-center mb-3 mb-xl-0 overflow-auto paginacao-custom">
                    <style>
                        .paginacao-custom .d-sm-flex {
                            flex-direction: column;
                            align-items: center;
                            gap: 12px;
                        }
                        .paginacao-custom p {
                            margin-bottom: 0 !important;
                        }
                    </style>
                    <div class="m-0">
                        {{ $paginator->links() }}
                    </div>
                </div>

                <div class="col-12 col-xl-4">
                    <form action="{{ route('certificados.emitir') }}" method="POST" class="m-0 d-flex justify-content-center justify-content-xl-end gap-2">
                        @csrf
                        <input type="hidden" name="session_key" value="{{ $sessionKey }}">
                        <a href="{{ route('eventos.index') }}" class="btn btn-light border">Voltar</a>

                        <button type="submit" class="btn btn-engaja" {{ $totalProntos === 0 ? 'disabled' : '' }}>
                        Confirmar Emissão
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
