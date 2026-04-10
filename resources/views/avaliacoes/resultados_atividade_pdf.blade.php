@php
    use Carbon\Carbon;
    $palette = ['#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D', '#601F69', '#6C345E', '#9602C7'];
    $eventoNome = $atividade->evento->nome ?? '—';
    $diaFmt = $atividade->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : '—';
    $municipiosTxt = $atividade->municipios->isNotEmpty()
        ? $atividade->municipios->map(fn ($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
        : '—';
    $templateNome = $avaliacao->templateAvaliacao->nome ?? '—';
    $imgLogo = public_path('images/engaja-bg.png');
    $imgIpf = public_path('images/ipf.png');
    $imgPetro = public_path('images/petrobras.png');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Avaliação — {{ $atividade->descricao }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 12px 14px 24px; }
        .pdf-header { border: 1px solid #edd7fc; border-radius: 6px; background: #f9f4ff; margin-bottom: 14px; padding: 10px 12px; }
        .pdf-header table { width: 100%; border-collapse: collapse; }
        .pdf-header td { vertical-align: middle; padding: 4px; border: none; }
        .pdf-header img { height: 44px; }
        .pdf-header h1 { font-size: 16px; margin: 0 0 4px 0; color: #421944; font-weight: bold; }
        .pdf-header .subtitle { font-size: 10px; text-transform: uppercase; letter-spacing: 0.35px; color: #421944; margin: 0; }
        .pdf-header .meta { font-size: 10px; color: #555; margin: 4px 0 0 0; }
        .intro-box { border: 1px solid #e8dff0; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; background: #fff; }
        .intro-box p { margin: 0 0 4px 0; }
        .intro-box strong { color: #421944; }
        .metrics { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .metrics td { border: 1px solid #edd7fc; background: #fcfaff; padding: 8px 10px; width: 25%; vertical-align: top; }
        .metric-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.35px; color: #6b7a99; display: block; margin-bottom: 2px; }
        .metric-value { font-size: 15px; font-weight: bold; color: #421944; }
        .dim-title { font-size: 13px; font-weight: bold; color: #421944; margin: 14px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #edd7fc; page-break-after: avoid; }
        .ind-title { font-size: 11px; font-weight: bold; color: #333; margin: 10px 0 6px 0; page-break-after: avoid; }
        .question-block { border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; page-break-inside: avoid; background: #fff; }
        .question-num { color: #666; font-size: 10px; margin-right: 4px; }
        .question-text { font-weight: bold; color: #222; margin-bottom: 6px; }
        .muted { color: #666; font-size: 10px; }
        .bar-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .bar-table td { padding: 3px 4px; vertical-align: middle; border: none; font-size: 10px; }
        .bar-label { width: 30%; word-break: break-word; }
        .bar-cell { width: 58%; }
        .bar-fill-wrap { width: 100%; height: 12px; background: #eee; border-radius: 2px; overflow: hidden; }
        .bar-fill { height: 12px; border-radius: 2px; }
        .bar-count { width: 12%; text-align: right; font-weight: bold; }
        .text-answer { border-left: 3px solid #421944; padding: 4px 8px; margin: 4px 0; background: #fafafa; font-size: 10px; word-break: break-word; }
        .pdf-footer { margin-top: 20px; padding-top: 12px; border-top: 1px solid #edd7fc; font-size: 9px; color: #444; }
        .pdf-footer-partners { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .pdf-footer-partners td { text-align: center; vertical-align: top; padding: 6px; width: 50%; border: none; }
        .pdf-footer-partners .lbl { color: #421944; font-weight: bold; margin-bottom: 4px; }
        .pdf-footer-partners img { max-height: 32px; }
        .footer-legal { text-align: center; font-size: 8px; color: #666; margin-top: 8px; }
        .empty-state { border: 1px dashed #ccc; padding: 16px; text-align: center; color: #777; margin: 12px 0; border-radius: 6px; }
    </style>
</head>
<body>

    <header class="pdf-header">
        <table>
            <tr>
                <td style="width: 90px;">
                    @if(file_exists($imgLogo))
                        <img src="{{ $imgLogo }}" alt="Engaja">
                    @endif
                </td>
                <td>
                    <p class="subtitle">Relatório de respostas · Avaliação anónima</p>
                    <h1>Avaliação — {{ $atividade->descricao }}</h1>
                    <p class="meta">Gerado em {{ $geradoEm->format('d/m/Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </header>

    <div class="intro-box">
        <p><strong>Ação pedagógica:</strong> {{ $eventoNome }}</p>
        <p><strong>Data:</strong> {{ $diaFmt }} &nbsp;·&nbsp; <strong>Município(s):</strong> {{ $municipiosTxt }}</p>
        <p><strong>Modelo de formulário:</strong> {{ $templateNome }}</p>
    </div>

    <table class="metrics">
        <tr>
            <td>
                <span class="metric-label">Submissões</span>
                <span class="metric-value">{{ number_format($totais['submissoes'] ?? 0, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="metric-label">Questões com resposta</span>
                <span class="metric-value">{{ number_format($totais['questoes'] ?? 0, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="metric-label">Respostas (itens)</span>
                <span class="metric-value">{{ number_format($totais['respostas'] ?? 0, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="metric-label">Última resposta</span>
                <span class="metric-value" style="font-size: 11px;">{{ $totais['ultima'] ?? '—' }}</span>
            </td>
        </tr>
    </table>

    @if(empty($perguntas))
        <div class="empty-state">Nenhuma resposta agregada para este momento no momento da geração do PDF.</div>
    @else
        @php
            $dimAtual = null;
            $indAtual = null;
            $numQuestao = 0;
        @endphp
        @foreach($perguntas as $p)
            @php
                $dim = $p['dimensao'] ?? 'Sem dimensão';
                $ind = $p['indicador'] ?? 'Sem indicador';
            @endphp
            @if($dim !== $dimAtual)
                @php $dimAtual = $dim; $indAtual = null; @endphp
                <div class="dim-title">Dimensão — {{ $dim }}</div>
            @endif
            @if($ind !== $indAtual)
                @php $indAtual = $ind; @endphp
                <div class="ind-title">Indicador — {{ $ind }}</div>
            @endif
            @php
                $numQuestao++;
                $tipo = $p['tipo'] ?? 'texto';
                $labels = array_values(collect($p['labels'] ?? [])->all());
                $values = array_values(collect($p['values'] ?? [])->all());
            @endphp

            <div class="question-block">
                <div class="question-text">
                    <span class="question-num">{{ $numQuestao }}.</span>
                    {{ $p['texto'] ?? 'Questão' }}
                </div>
                <div class="muted">{{ $p['total'] ?? 0 }} resposta(s)
                    @if(!empty($p['resumo']))
                        · {{ $p['resumo'] }}
                    @endif
                </div>

                @if($tipo === 'texto')
                    @php $lista = $p['respostas'] ?? []; @endphp
                    @if(empty($lista))
                        <p class="muted" style="margin-top:6px;">Sem respostas de texto.</p>
                    @else
                        @foreach($lista as $txt)
                            <div class="text-answer">{{ $txt }}</div>
                        @endforeach
                        @if(!empty($p['respostas_truncadas']))
                            <p class="muted" style="margin-top:8px;">Nota: lista truncada ao gerar o resumo ({{ $p['respostas_total'] ?? count($lista) }} resposta(s) no total).</p>
                        @endif
                    @endif
                @else
                    @php
                        $totalBarras = ! empty($values) ? max(1, array_sum($values)) : 1;
                    @endphp
                    <p class="muted" style="margin:4px 0 2px 0;">Barras: percentagem em relação ao total de respostas nesta questão (soma das opções).</p>
                    <table class="bar-table">
                        @foreach($labels as $idx => $label)
                            @php
                                $rawLabel = (string) $label;
                                $labelShow = $rawLabel === 'Nao' ? 'Não' : $rawLabel;
                                $val = (int) ($values[$idx] ?? 0);
                                $pct = round(($val / $totalBarras) * 100);
                                $color = $palette[$idx % count($palette)];
                            @endphp
                            <tr>
                                <td class="bar-label">{{ $labelShow }}</td>
                                <td class="bar-cell">
                                    <div class="bar-fill-wrap">
                                        <div class="bar-fill" style="width: {{ $pct }}%; background: {{ $color }};"></div>
                                    </div>
                                </td>
                                <td class="bar-count">{{ $val }}</td>
                            </tr>
                        @endforeach
                    </table>
                @endif
            </div>
        @endforeach
    @endif

    <footer class="pdf-footer">
        <p style="margin:0 0 8px 0; color:#421944; font-weight:bold;">Sistema de Gestão de Participação e Engajamento.</p>
        <table class="pdf-footer-partners">
            <tr>
                <td>
                    <div class="lbl">Realização</div>
                    @if(file_exists($imgIpf))
                        <img src="{{ $imgIpf }}" alt="IPF" style="max-height:36px;">
                    @endif
                </td>
                <td>
                    <div class="lbl">Parceria</div>
                    @if(file_exists($imgPetro))
                        <img src="{{ $imgPetro }}" alt="Petrobras" style="max-height:36px;">
                    @endif
                </td>
            </tr>
        </table>
        <div class="footer-legal">
            INSTITUTO DE EDUCAÇÃO E DIREITOS HUMANOS PAULO FREIRE | CNPJ 04.950.603/0001-05
        </div>
    </footer>

</body>
</html>
