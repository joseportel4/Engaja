@extends('layouts.pdf-alfa-eja-landscape')

@section('title', 'Total Geral de Participantes')

@section('styles')
    .table-header {
        font-size: 11px;
        font-weight: bold;
        background-color: #f0f0f0;
        padding: 4px;
    }
    .table-data {
        font-size: 10px;
        padding: 3px;
    }
    .text-end {
        text-align: right;
    }
    .unidentified-row {
        background-color: #f8f5f0;
        font-size: 10px;
    }
    .total-row {
        background-color: #e8daea;
        font-weight: bold;
        font-size: 10px;
    }
@endsection

@section('content')
<h2>Total Geral de Participantes</h2>

{{-- Filtros Aplicados --}}
@php
    $filtrosAplicados = [];
    if (request('evento_id')) {
        $evento = \App\Models\Evento::find(request('evento_id'));
        if ($evento) $filtrosAplicados[] = "Ação: " . $evento->nome;
    }
    if (request('regiao_id')) {
        $regiao = \App\Models\Regiao::find(request('regiao_id'));
        if ($regiao) $filtrosAplicados[] = "Região: " . $regiao->nome;
    }
    if (request('municipio_id')) {
        $municipio = \App\Models\Municipio::find(request('municipio_id'));
        if ($municipio) $filtrosAplicados[] = "Município: " . $municipio->nome;
    }
    if (request('de') || request('ate')) {
        $de = request('de') ? \Carbon\Carbon::parse(request('de'))->format('d/m/Y') : '';
        $ate = request('ate') ? \Carbon\Carbon::parse(request('ate'))->format('d/m/Y') : '';
        $intervalo = ($de && $ate) ? "$de até $ate" : ($de ? "a partir de $de" : "até $ate");
        $filtrosAplicados[] = "Período: " . $intervalo;
    }
@endphp

@if(count($filtrosAplicados) > 0)
<div style="margin-bottom: 15px; padding: 10px; background-color: #f9f9f9; border-left: 3px solid #421944;">
    <p style="margin: 0; font-size: 11px; color: #555; font-weight: bold;">Filtros Aplicados:</p>
    <ul style="margin: 5px 0 0 20px; font-size: 10px; color: #666;">
        @foreach($filtrosAplicados as $filtro)
            <li>{{ $filtro }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($totalGeral->filter(fn($r) => !isset($r['_is_total']))->isEmpty())
    <p style="text-align: center; color: #666;">Nenhum dado encontrado com os filtros aplicados.</p>
@else
<table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd;">
    <thead>
        <tr>
            <th class="table-header">Região</th>
            <th class="table-header">Município</th>
            <th class="table-header text-end">Previstos</th>
            <th class="table-header text-end">Com CPF</th>
            <th class="table-header text-end">Sem CPF</th>
            <th class="table-header text-end">% Com CPF</th>
        </tr>
    </thead>
    <tbody>
        @foreach($totalGeral as $row)
            @if(isset($row['_is_total']))
            <tr class="total-row">
                <td colspan="2" style="text-align: right; padding: 3px;">{{ $row['municipio_nome'] }}</td>
                <td class="text-end">{{ $row['previstos'] ?: '—' }}</td>
                <td class="text-end">{{ $row['metricas']['cpf']['com'] }}</td>
                <td class="text-end">{{ $row['metricas']['cpf']['sem'] }}</td>
                <td class="text-end">{{ ($row['metricas']['cpf']['com'] + $row['metricas']['cpf']['sem']) > 0 ? number_format($row['metricas']['cpf']['pct'], 2, ',', '.') . '%' : '—' }}</td>
            </tr>
            @elseif(isset($row['_is_unidentified']))
            <tr class="unidentified-row" style="border-bottom: 1px solid #ddd;">
                <td colspan="2" class="table-data">{{ $row['municipio_nome'] }}</td>
                <td class="table-data text-end">{{ $row['previstos'] ?: '—' }}</td>
                <td class="table-data text-end">{{ $row['metricas']['cpf']['com'] }}</td>
                <td class="table-data text-end">{{ $row['metricas']['cpf']['sem'] }}</td>
                <td class="table-data text-end">{{ ($row['metricas']['cpf']['com'] + $row['metricas']['cpf']['sem']) > 0 ? $row['metricas']['cpf']['pct'] . '%' : '—' }}</td>
            </tr>
            @else
            <tr style="border-bottom: 1px solid #ddd;">
                <td class="table-data">{{ $row['regiao'] }}</td>
                <td class="table-data">{{ $row['municipio_nome'] }}</td>
                <td class="table-data text-end">{{ $row['previstos'] ?: '—' }}</td>
                <td class="table-data text-end">{{ $row['metricas']['cpf']['com'] }}</td>
                <td class="table-data text-end">{{ $row['metricas']['cpf']['sem'] }}</td>
                <td class="table-data text-end">{{ ($row['metricas']['cpf']['com'] + $row['metricas']['cpf']['sem']) > 0 ? $row['metricas']['cpf']['pct'] . '%' : '—' }}</td>
            </tr>
            @endif
        @endforeach
    </tbody>
</table>
@endif
@endsection
