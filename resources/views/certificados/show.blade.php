<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Certificado</title>
  <style>
    :root { --page-width: 1120px; }
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      background: #f7f7f7;
    }
    .wrapper {
      max-width: var(--page-width);
      margin: 20px auto;
      padding: 12px;
    }
    .card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      padding: 12px;
    }
    .cert-page {
      position: relative;
      width: 100%;
      border: 1px solid #e5e7eb;
      border-radius: 6px;
      overflow: hidden;
      margin-bottom: 24px;
      page-break-after: always;
    }
    .cert-bg {
      width: 100%;
      height: auto;
      display: block;
    }
    .cert-text {
      position: absolute;
      left: 10%;
      top: 20%;
      right: 10%;
      color: #111;
      font-size: 20px;
      line-height: 1.4;
      white-space: pre-wrap;
    }
    .actions {
      display: flex;
      gap: 10px;
      margin-bottom: 12px;
    }
    .btn {
      padding: 10px 14px;
      border: 1px solid #6c2a6a;
      background: #6c2a6a;
      color: #fff;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
    }
    .btn-outline { background: #fff; color: #6c2a6a; }

    @media print {
      body { background: #fff; }
      .wrapper { margin: 0; padding: 0; max-width: 100%; }
      .actions { display: none !important; }
      .card { border: none; box-shadow: none; padding: 0; }
      @page { size: A4 landscape; margin: 10mm; }
      .cert-page { page-break-after: always; border: none; border-radius: 0; margin: 0 0 10mm 0; }
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="actions">
      <a href="{{ route('certificados.download', $certificado) }}" class="btn">Baixar PDF</a>
      <a href="{{ url()->previous() }}" class="btn btn-outline">Voltar</a>
    </div>

    <div class="card">
      @php
        $modelo = $certificado->modelo;
        $frenteUrl = $modelo?->imagem_frente ? asset('storage/'.$modelo->imagem_frente) : '';
        $versoUrl = $modelo?->imagem_verso ? asset('storage/'.$modelo->imagem_verso) : '';
      @endphp

      <div class="cert-page">
        @if($frenteUrl)
          <img src="{{ $frenteUrl }}" alt="Frente" class="cert-bg">
        @endif
        <div class="cert-text">{!! nl2br(e($certificado->texto_frente)) !!}</div>
      </div>

      @if($versoUrl || $certificado->texto_verso)
      <div class="cert-page">
        @if($versoUrl)
          <img src="{{ $versoUrl }}" alt="Verso" class="cert-bg">
        @endif
        <div class="cert-text">{!! nl2br(e($certificado->texto_verso)) !!}</div>
      </div>
      @endif
    </div>
  </div>
</body>
</html>
