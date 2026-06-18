<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Documento')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            height: 150mm;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        @yield('styles')
    </style>
</head>
<body>
    @php
        $headerPath = public_path('images/Alfa-Eja Header.png');
        $footerPath = public_path('images/Alfa-Eja Footer.png');
    @endphp
    @if(file_exists($headerPath))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($headerPath)) }}" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none" aria-hidden="true">
    @endif
    @if(file_exists($footerPath))
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents($footerPath)) }}" style="position:absolute;width:0;height:0;opacity:0;pointer-events:none" aria-hidden="true">
    @endif
    @yield('content')
</body>
</html>
