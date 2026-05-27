@php
  $url = route('profile.certificados');
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: 'Montserrat', Arial, sans-serif;
      background: #f5f3fa;
      padding: 0;
      margin: 0;
      -webkit-font-smoothing: antialiased;
    }
    .wrapper {
      max-width: 720px;
      margin: 0 auto;
      padding: 32px 16px;
      text-align: center;
    }
    .logo {
      display: inline-block;
      margin-bottom: 16px;
    }
    .logo img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 28px 28px 24px 28px;
      box-shadow: 0 14px 35px rgba(0,0,0,0.06);
      text-align: left;
    }
    .card-title {
        font-size: 20px;
        color: #000000;
        margin: 0 0 16px 0;
        font-weight: 600;
    }
    .card-text {
        color: #000000;
        font-size: 15px;
        line-height: 1.7;
        margin: 0 0 16px 0;
        font-weight: 400;
        text-align: justify;
    }
    .card-info {
        color: #000000;
        font-size: 15px;
        margin-bottom: 8px;
        font-weight: 400;
        text-align: justify;
    }
    p {
      color: #000000;
      font-size: 15px;
      line-height: 1.6;
      margin: 0 0 14px 0;
    }
    .hashtags {
        color: #000000;
        font-weight: bold;
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 16px;
        text-align: justify;

    }
    .signature {
        font-size: 16px;
        color: #000000;
        margin-top: 24px;
    }
    .btn {
      display: inline-block;
      background: #4a0e4e;
      color: #fff;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
      margin: 6px 0 14px 0;
    }
    .muted {
      font-size: 13px;
      color: #6b7280;
      word-break: break-all;
    }
  </style>
</head>
<body>
  <div class="wrapper">
      <div class="logo">
          @if(isset($bannerPath) && file_exists($bannerPath))
              <img src="{{ $message->embed($bannerPath) }}" alt="Banner - Participar Para Transformar">
          @endif
      </div>
      <div class="card">
          <p class="card-title">Caro(a) {{ $nome }},</p>

          <p class="card-text">
              O seu certificado referente à ação pedagógica <strong>{{ $acao }}</strong> foi emitido com sucesso e <strong>encontra-se em anexo neste e-mail!</strong>
          </p>

          <p class="card-info"><strong>Realização:</strong> Instituto Paulo Freire. <strong>Parceria:</strong> SMDHC de São Paulo e apoio do CRECE Central.</p>
          <p class="card-info"><strong>Dúvidas:</strong> participarparatransformar@paulofreire.org</p>

          <br>

          <div class="hashtags">
              #InstitutoPauloFreire #IPF34anos #EaDFreiriana #PauloFreire #PauloFreireSim #PauloFreireSempre #PauloFreireVive #PauloFreirePresente #patronodaeducaçãobrasileira #Educação #DireitosHumanos
          </div>

          <div class="signature">
              <p style="margin-bottom: 4px;">Grande abraço,</p>
              <strong>#EquipeEaDFreiriana</strong><br>
              Instituto Paulo Freire
          </div>

          <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0;">

          <p class="muted">Esta é uma mensagem automática. Não responda a este e-mail.</p>
      </div>
  </div>
</body>
</html>
