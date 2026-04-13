<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório da Ação</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; background: #ffffff; margin: 0; padding: 0; }
        .sheet { background: #ffffff; border: 0; border-radius: 0; overflow: hidden; }
        .content { padding: 20px 18px 16px 18px; }

        .brand-header { background: #ffffff; color: #481d42; padding: 0 18px 10px 18px; border-bottom: 1px solid #e5e7eb; }
        .brand-top-accent { height: 6px; background: #481d42; margin: 0 -18px 12px -18px; }
        .brand-title { text-align: center; margin-bottom: 8px; }
        .brand-title img { height: 38px; }
        .brand-subtitle { text-align: center; font-size: 11px; color: #6b7280; margin-top: 4px; }
        .brand-line { border-top: 1px solid #e5e7eb; margin: 10px 0 12px 0; }
        .brand-partners { width: 100%; border-collapse: collapse; }
        .brand-partners td { border: 0; text-align: center; vertical-align: middle; }
        .brand-label { font-size: 10px; color: #6b7280; margin-bottom: 6px; }
        .brand-logo-ipf { height: 34px; }
        .brand-logo-petro { height: 29px; }

        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .title-row td { border: 0; vertical-align: bottom; }
        .report-title { font-size: 28px; font-weight: 700; margin: 0 0 3px 0; color: #111827; }
        .author { color: #6b7280; font-size: 12px; }
        .author-name { color: #481d42; font-weight: 700; }

        .card { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 18px; }
        .table-clean { width: 100%; border-collapse: collapse; }
        .table-clean td, .table-clean th { border: 1px solid #e5e7eb; padding: 10px 12px; vertical-align: top; }
        .table-clean th { text-align: left; background: #f8fafc; color: #374151; font-weight: 700; }
        .table-clean .value { background: #ffffff; color: #4b5563; }
        .table-clean .value-number { text-align: right; color: #481d42; font-weight: 700; background: #f8fafc; width: 90px; }

        .section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 10px 0; border-left: 6px solid #481d42; padding-left: 10px; }
        .qa-item { margin-bottom: 14px; }
        .qa-question { font-size: 12px; font-weight: 700; color: #111827; margin: 0 0 6px 2px; }
        .qa-answer { border: 1px solid #e5e7eb; border-left: 4px solid #481d42; background: #f8fafc; border-radius: 0 10px 10px 3px; padding: 10px; color: #374151; }

        .checklist-wrap { border-top: 1px solid #e5e7eb; padding-top: 14px; margin-top: 6px; }
        .checklist-list { margin: 0; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; list-style: none; }
        .checklist-list li { margin-bottom: 9px; color: #374151; }
        .checklist-list li:last-child { margin-bottom: 0; }
        .check-bullet { display: inline-block; width: 8px; height: 8px; margin-right: 7px; border-radius: 50%; background: #22c55e; }
        .muted { color: #6b7280; }

        .footer-note { background: #ffffff; border-top: 1px solid #e5e7eb; padding: 12px 20px; text-align: center; color: #6b7280; font-size: 10px; }
    </style>
</head>
<body>
@php
    $atividade = $relatorio->atividade;
    $evento = $atividade?->evento;
    $nomeResponsavel = $relatorio->user?->name ?? $relatorio->nome_educador ?? 'Usuário não identificado';
    $checklistSalvo = $relatorio->checklist_pos_acao ?? [];
    $logoDataUri = function (string $relativePath): ?string {
        $fullPath = public_path($relativePath);

        if (!is_file($fullPath)) {
            return null;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'application/octet-stream',
        };

        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
    };

    $logoEngaja = $logoDataUri('images/engaja-bg.png');
    $logoIpf = $logoDataUri('images/ipf.png');
    $logoPetrobras = $logoDataUri('images/petrobras.png');

    $checklistLabels = [
        'upload_evidencias'       => 'Fez o upload das evidências (fotos, vídeos com depoimentos) na pasta correspondente a essa ação dentro do Drive',
        'lista_presenca_digital'  => 'Conferiu as listas de presença digital (link acima), garantindo que todos os campos estejam devidamente preenchidos',
        'lista_presenca_impressa' => 'Conferiu as listas de presença impressa, garantindo que todos os campos estejam devidamente preenchidos',
        'upload_lista_impressa'   => 'Fez o upload das listas de presença impressas na pasta dentro do Drive, depois de devidamente conferida e ajustada',
    ];
@endphp

<div class="sheet">
    <div class="brand-header">
        <div class="brand-top-accent"></div>
        <div class="brand-title">
            @if($logoEngaja)
                <img src="{{ $logoEngaja }}" alt="Engaja">
            @endif
            <div class="brand-subtitle">Sistema de Gestão de Participação e Engajamento.</div>
        </div>
        <div class="brand-line"></div>
        <table class="brand-partners">
            <tr>
                <td>
                    <div class="brand-label">Realização</div>
                    @if($logoIpf)
                        <img src="{{ $logoIpf }}" class="brand-logo-ipf" alt="Instituto Paulo Freire">
                    @endif
                </td>
                <td>
                    <div class="brand-label">Parceria</div>
                    @if($logoPetrobras)
                        <img src="{{ $logoPetrobras }}" class="brand-logo-petro" alt="Petrobras">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <table class="title-row">
            <tr>
                <td>
                    <h1 class="report-title">Relatório da Ação</h1>
                    <div class="author">Preenchido por: <span class="author-name">{{ $nomeResponsavel }}</span></div>
                </td>
                <td style="text-align: right; color: #9ca3af; font-size: 10px;">
                    Documento institucional
                </td>
            </tr>
        </table>

        <div class="card">
            <table class="table-clean">
                <tr>
                    <th style="width: 25%;">Ação pedagógica</th>
                    <td class="value">{{ $evento?->nome ?? '—' }}</td>
                    <th style="width: 15%;">Momento</th>
                    <td class="value">{{ $atividade?->descricao ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Data</th>
                    <td class="value">{{ $atividade?->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}</td>
                    <th>Horário</th>
                    <td class="value">
                        {{ $atividade?->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} -
                        {{ $atividade?->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
                    </td>
                </tr>
            </table>
        </div>

        <h2 class="section-title">Quadro Resumo de Público</h2>
        <div class="card">
            <table class="table-clean">
                <tr><td>Quantidade prevista de participantes</td><td class="value-number">{{ $resumoPublico['prevista'] ?? 0 }}</td></tr>
                <tr><td>Quantidade de inscritos</td><td class="value-number">{{ $resumoPublico['inscritos'] ?? 0 }}</td></tr>
                <tr><td>Quantidade de presentes na ação</td><td class="value-number">{{ $resumoPublico['presentes'] ?? 0 }}</td></tr>
                <tr><td>Participantes ligados aos movimentos sociais</td><td class="value-number">{{ $resumoPublico['movimentos'] ?? 0 }}</td></tr>
                <tr><td>Participantes com vínculo com a prefeitura</td><td class="value-number">{{ $resumoPublico['prefeitura'] ?? 0 }}</td></tr>
            </table>
        </div>

        <h2 class="section-title">Perguntas e Respostas</h2>
        @foreach($camposPerguntas as $campo => $pergunta)
            <div class="qa-item">
                <div class="qa-question">{{ $pergunta }}</div>
                <div class="qa-answer">{{ $relatorio->$campo ?: '—' }}</div>
            </div>
        @endforeach

        <div class="checklist-wrap">
            <h2 class="section-title">Checklist Pós-ação</h2>
            @if(empty($checklistSalvo))
                <p class="muted">Nenhum item marcado.</p>
            @else
                <ul class="checklist-list">
                    @foreach($checklistLabels as $valor => $label)
                        @if(in_array($valor, $checklistSalvo, true))
                            <li><span class="check-bullet"></span>{{ $label }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    <div class="footer-note">INSTITUTO DE EDUCACAO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05</div>
</div>

</body>
</html>
