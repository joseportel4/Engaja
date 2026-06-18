@php
    $pdfFooterPath = public_path('images/Alfa-Eja Footer.png');
    $pdfFooterB64 = file_exists($pdfFooterPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfFooterPath))
        : '';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; }
        body { margin: 0; padding: 0; text-align: center; }
        img { max-width: 150px; height: auto; display: inline-block; }
    </style>
</head>
<body>
    @if($pdfFooterB64)
        <img src="{{ $pdfFooterB64 }}" alt="Parceiros Alfa-EJA">
    @endif
</body>
</html>
