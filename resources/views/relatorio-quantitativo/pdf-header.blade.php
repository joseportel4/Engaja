@php
    $pdfHeaderPath = public_path('images/Alfa-Eja Header.png');
    $pdfHeaderB64 = file_exists($pdfHeaderPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($pdfHeaderPath))
        : '';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; }
        body { margin: 0; padding: 0; }
        img { max-width: 100%; height: auto; display: block; }
    </style>
</head>
<body>
    @if($pdfHeaderB64)
        <img src="{{ $pdfHeaderB64 }}" alt="Alfa-EJA Brasil">
    @endif
</body>
</html>
