<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Lote de Cartas</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; line-height: 1.5; color: #333; }
        .page-break { page-break-after: always; }
        .carta-container { margin-bottom: 40px; }
        .header { background: #f4f4f4; padding: 15px; margin-bottom: 20px; border-bottom: 2px solid #ccc; }
        .header h2 { margin: 0 0 5px 0; font-size: 18px; }
        .header p { margin: 0; font-size: 14px; color: #666; }
        .mensagem { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px; }
        .mensagem-header { font-weight: bold; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        .anexo-aviso { color: #888; font-style: italic; }
    </style>
</head>
<body>
    @if($cartas->isEmpty())
        <h2>Nenhuma carta encontrada para os filtros selecionados.</h2>
    @else
        @foreach($cartas as $carta)
            <div class="carta-container">
                <div class="header">
                    @php
                        $logoPath = public_path('images/cartas/cartas-logo.png');
                        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                    @endphp
                    @if($logoBase64)
                        <div style="text-align: right; margin-bottom: -30px;">
                            <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Cartas para Esperançar" style="max-height: 40px;">
                        </div>
                    @endif
                    <h2>Carta: {{ $carta->codigo }}</h2>
                    <p>Enviada por: {{ $carta->educando->user->name ?? 'Educando não identificado' }} 
                       @if($carta->educando && $carta->educando->municipio)
                       ({{ $carta->educando->municipio->nome }} - {{ $carta->educando->municipio->estado->sigla ?? '' }})
                       @endif
                    </p>
                    <p>Voluntário(a): {{ $carta->voluntario->name ?? 'Não atribuído' }}</p>
                </div>

                @foreach($carta->mensagens as $mensagem)
                    <div class="mensagem">
                        <div class="mensagem-header">
                            @if($mensagem->tipo_remetente === \App\Models\Cartas\CartaMensagem::TIPO_REMETENTE_EDUCANDO)
                                De: Educando(a) {{ $carta->educando->user->name ?? '' }}
                            @else
                                De: Voluntário(a) {{ $carta->voluntario->name ?? '' }}
                            @endif
                            - Rodada {{ $mensagem->rodada }}
                        </div>
                        
                        <div class="mensagem-body">
                            @if($mensagem->texto)
                                {!! nl2br(e($mensagem->texto)) !!}
                            @endif

                            @php
                                $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
                                $mime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime;
                                $isImage = str_starts_with((string) $mime, 'image/');
                                $isPdf = $mime === 'application/pdf';
                            @endphp

                            @if($path)
                                @if($isImage && Storage::disk('local')->exists($path))
                                    @php
                                        $base64 = base64_encode(Storage::disk('local')->get($path));
                                    @endphp
                                    <div style="margin-top: 15px; text-align: center;">
                                        <img src="data:{{ $mime }};base64,{{ $base64 }}" style="max-width: 100%; max-height: 800px; border: 1px solid #ccc;">
                                    </div>
                                @elseif($isPdf && Storage::disk('local')->exists($path))
                                    <div class="anexo-aviso" style="margin-top: 15px; border: 1px dashed #ccc; padding: 15px; text-align: center;">
                                        <i>[Carta anexa em formato PDF. As páginas do PDF original foram mescladas a este lote logo após esta página.]</i>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            
            @if(!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach
    @endif
</body>
</html>
