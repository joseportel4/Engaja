@extends('layouts.pdf-alfa-eja')

@section('title', 'Lista de Presença')

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }

    .doc-header { background: #421944; color: #ffffff; padding: 12px 16px; margin-top: 20px; margin-bottom: 16px; }
    .doc-header h1 { font-size: 16px; font-weight: 700; margin: 0 0 2px 0; }
    .doc-header .subtitle { font-size: 10px; opacity: 0.85; }

    .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .info-grid td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    .info-grid .label { background: #f3f4f6; font-weight: 700; color: #374151; width: 22%; }

    .section-title { font-size: 13px; font-weight: 700; color: #421944; border-left: 5px solid #421944; padding-left: 8px; margin: 0 0 10px 0; }

    .participant-list { list-style: none; margin: 0; padding: 0; }
    .participant-item { display: table; width: 100%; border-bottom: 1px solid #e5e7eb; padding: 7px 0; }
    .participant-item:last-child { border-bottom: none; }
    .item-num { display: table-cell; width: 26px; font-weight: 700; color: #9ca3af; vertical-align: middle; }
    .item-body { display: table-cell; vertical-align: middle; }
    .item-name { font-weight: 700; color: #111827; font-size: 11px; }
    .item-details { color: #6b7280; font-size: 10px; margin-top: 2px; }
    .badge-ouvinte { background: #f5f3ff; color: #2c1230; border-radius: 4px; padding: 1px 5px; font-size: 9px; font-weight: 700; margin-left: 5px; }

    .total { text-align: right; font-size: 10px; color: #6b7280; margin-top: 10px; }
    .empty { color: #9ca3af; font-style: italic; padding: 12px 0; }
@endsection

@section('content')
@php
    $ini = \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i');
    $fim = \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i');
    $data = \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y');
    $municipio = $atividade->municipio;
    $munLabel = $municipio ? ($municipio->nome . ' / ' . ($municipio->estado->sigla ?? '')) : '—';
@endphp

<div class="doc-header">
    <h1>Lista de Presença</h1>
    <div class="subtitle">Documento gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>
</div>

<table class="info-grid">
    <tr>
        <td class="label">Momento</td>
        <td colspan="3">{{ $atividade->descricao }}</td>
    </tr>
    <tr>
        <td class="label">Data</td>
        <td>{{ $data }}</td>
        <td class="label">Horário</td>
        <td>{{ $ini }} às {{ $fim }}</td>
    </tr>
    <tr>
        <td class="label">Município</td>
        <td colspan="3">{{ $munLabel }}</td>
    </tr>
    @if($atividade->evento)
    <tr>
        <td class="label">Ação pedagógica</td>
        <td colspan="3">{{ $atividade->evento->nome }}</td>
    </tr>
    @endif
</table>

<h2 class="section-title">Participantes</h2>

@if($inscricoes->isEmpty())
    <p class="empty">Nenhum participante registrado para este momento.</p>
@else
    <ul class="participant-list">
        @foreach($inscricoes as $i => $inscricao)
            @php
                $p = $inscricao->participante;
                $u = $p?->user;
                $m = $p?->municipio;
                $munPart = $m ? ($m->nome . ' - ' . ($m->estado?->sigla ?? '')) : null;
            @endphp
            <li class="participant-item">
                <span class="item-num">{{ $i + 1 }}</span>
                <span class="item-body">
                    <div class="item-name">
                        {{ $u->name ?? '—' }}
                        @if($inscricao->ouvinte)
                            <span class="badge-ouvinte">Ouvinte</span>
                        @endif
                    </div>
                    <div class="item-details">
                        {{ $u->email ?? '' }}
                        @if($munPart)
                            &nbsp;·&nbsp; {{ $munPart }}
                        @endif
                    </div>
                </span>
            </li>
        @endforeach
    </ul>
    <div class="total">Total: {{ $inscricoes->count() }} participante(s)</div>
@endif
@endsection
