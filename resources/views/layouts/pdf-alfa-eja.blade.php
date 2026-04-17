@php
    $pdfHeaderPath = public_path('images/Alfa-Eja Header.png');
    $pdfFooterPath = public_path('images/Alfa-Eja Footer.png');
    $pdfHeaderB64 = file_exists($pdfHeaderPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfHeaderPath))
        : '';
    $pdfFooterB64 = file_exists($pdfFooterPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfFooterPath))
        : '';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        @page { margin: 28mm 14mm 22mm 14mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 0; padding: 0; }
        /* left: -14mm compensa a margem lateral do @page para o header ocupar a largura total da página */
        .pdf-brand-header { position: fixed; top: -28mm; left: -14mm; width: 210mm; }
        .pdf-brand-header img { width: 210mm; display: block; }
        /* Footer centralizado em 1/3 da largura do content area (182mm) */
        /* top: 250mm = content area A4 portrait (247mm) + 3mm gap */
        .pdf-brand-footer { position: fixed; top: 250mm; left: 61mm; width: 61mm; }
        .pdf-brand-footer img { width: 61mm; display: block; }
        @yield('styles')
    </style>
</head>
<body>
    @if($pdfHeaderB64)
        <div class="pdf-brand-header">
            <img src="{{ $pdfHeaderB64 }}" alt="Alfa-EJA Brasil">
        </div>
    @endif
    @if($pdfFooterB64)
        <div class="pdf-brand-footer">
            <img src="{{ $pdfFooterB64 }}" alt="Parceiros Alfa-EJA">
        </div>
    @endif
    @yield('content')
</body>
</html>
