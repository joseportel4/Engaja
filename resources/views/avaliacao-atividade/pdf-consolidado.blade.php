<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatórios do Momento — {{ $atividade->descricao ?? 'Momento' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; background: #ffffff; margin: 0; padding: 0; }
        .sheet { background: #ffffff; border: 0; border-radius: 0; overflow: hidden; }
        .content { padding: 20px 18px 16px 18px; }

        .report-header { background: #963d79; color: #ffffff; padding: 14px 18px; }
        .report-header-title { font-size: 24px; font-weight: 700; margin: 0; }
        .report-header-subtitle { font-size: 11px; opacity: 0.92; margin-top: 4px; }

        .title-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .title-row td { border: 0; vertical-align: bottom; }
        .report-title { font-size: 16px; font-weight: 700; margin: 0 0 3px 0; color: #111827; }
        .author { color: #6b7280; font-size: 12px; }

        .card { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 18px; }
        .table-clean { width: 100%; border-collapse: collapse; }
        .table-clean td, .table-clean th { border: 1px solid #e5e7eb; padding: 10px 12px; vertical-align: top; }
        .table-clean th { text-align: left; background: #963d79; color: #ffffff; font-weight: 700; }
        .table-clean .value { background: #ffffff; color: #4b5563; }
        .table-clean .value-number { text-align: right; color: #963d79; font-weight: 700; background: #f8fafc; width: 90px; }

        .section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 10px 0; border-left: 6px solid #963d79; padding-left: 10px; }
        .section-subtitle { color: #6b7280; margin: 0 0 10px 0; }
        .qa-item { margin-bottom: 14px; }
        .qa-question { font-size: 12px; font-weight: 700; color: #111827; margin: 0 0 6px 2px; }
        .answer { border: 1px solid #e5e7eb; border-left: 4px solid #963d79; background: #f8fafc; border-radius: 0 10px 10px 3px; padding: 10px; color: #374151; }
        .answer-meta { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
        .separator { margin: 14px 0; border-top: 1px dashed #cbd5e1; }
        .muted { color: #6b7280; }

        .pdf-footer { border-top: 1px solid #e9d5e0; padding: 12px 20px; text-align: center; color: #6b7280; font-size: 10px; }
        .pdf-footer-subtitle { font-size: 15px; color: #2f1230; font-weight: 700; margin-bottom: 10px; text-align: left; }
        .pdf-footer-partners { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .pdf-footer-partners td { border: 0; text-align: center; vertical-align: middle; width: 50%; }
        .brand-label { font-size: 11px; color: #5a2a56; font-weight: 700; margin-bottom: 6px; }
        .brand-logo-ipf { height: 32px; }
        .brand-logo-petro { height: 28px; }
        .footer-note { font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $evento = $atividade->evento;
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
    <div class="report-header">
        <h1 class="report-header-title">Relatórios do Momento</h1>
        <div class="report-header-subtitle">Documento institucional consolidado</div>
    </div>

    <div class="content">
        <table class="title-row">
            <tr>
                <td>
                    <h1 class="report-title">Dados Consolidados do Momento</h1>
                    <div class="author">Todos os relatórios pós-ação para o mesmo momento.</div>
                </td>
                <td style="text-align: right; color: #9ca3af; font-size: 10px;">
                    {{ now()->format('d/m/Y H:i') }} | Total: {{ $relatorios->count() }} relatório(s)
                </td>
            </tr>
        </table>

        <div class="card">
            <table class="table-clean">
                <tr>
                    <th style="width: 25%;">Ação pedagógica</th>
                    <td class="value">{{ $evento->nome ?? '—' }}</td>
                    <th style="width: 15%;">Momento</th>
                    <td class="value">{{ $atividade->descricao ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Data</th>
                    <td class="value">{{ $atividade->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}</td>
                    <th>Horário</th>
                    <td class="value">
                        {{ $atividade->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} -
                        {{ $atividade->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
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

        <h2 class="section-title">Perguntas e Respostas Consolidadas</h2>
        <p class="section-subtitle">Cada pergunta abaixo reúne todas as respostas enviadas para este mesmo momento.</p>

        @foreach($respostasPorPergunta as $indexPergunta => $itemPergunta)
            <div class="qa-item">
                <div class="qa-question">{{ $itemPergunta['pergunta'] }}</div>

                @if($itemPergunta['respostas']->isEmpty())
                    <div class="answer muted">Nenhuma resposta registrada.</div>
                @else
                    @foreach($itemPergunta['respostas'] as $resposta)
                        <div class="answer" style="margin-top: 6px;">
                            <div class="answer-meta">
                                <strong>Responsável:</strong> {{ $resposta['responsavel_nome'] }}
                                @if(!empty($resposta['atualizado_em']))
                                    | <strong>Enviado em:</strong> {{ $resposta['atualizado_em']->format('d/m/Y') }}
                                @endif
                            </div>
                            <div>{{ $resposta['resposta'] }}</div>
                        </div>
                    @endforeach
                @endif
            </div>

            @if(! $loop->last)
                <div class="separator"></div>
            @endif
        @endforeach
    </div>

    <div class="pdf-footer">
        <div class="pdf-footer-subtitle">Sistema de Gestão de Participação e Engajamento.</div>
        <table class="pdf-footer-partners">
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
        <div class="footer-note">INSTITUTO DE EDUCACAO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05</div>
    </div>
</div>
</body>
</html>
