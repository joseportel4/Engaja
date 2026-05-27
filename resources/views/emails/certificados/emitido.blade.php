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
    .top-header {
        text-align: center;
        margin-bottom: 40px;
    }
    .top-header img {
        max-width: 200px;
        height: auto;
        margin-bottom: 16px;
    }
    .quote-box {
        font-size: 13px;
        color: #07474d;
        font-style: italic;
        line-height: 1.6;
        margin-bottom: 32px;
        text-align: center;
        padding: 0 40px;
    }
    .quote-author {
        text-align: right;
        font-size: 12px;
        font-style: normal;
        margin-top: 8px;
    }
    .content {
        text-align: left;
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
        color: #07474d;
        margin: 0 0 16px 0;
        font-weight: 600;
    }
    .card-text {
        color: #07474d;
        font-size: 15px;
        line-height: 1.7;
        margin: 0 0 16px 0;
        font-weight: 400;
        text-align: justify;
    }
    p {
      color: #07474d;
      font-size: 15px;
      line-height: 1.6;
      margin: 0 0 14px 0;
    }
    .signature {
        font-size: 16px;
        color: #07474d;
        margin-top: 24px;
    }
    .footer-banner {
        margin-top: 24px;
        text-align: left;
    }
    .footer-banner img {
        max-width: 350px;
        height: auto;
    }
  </style>
</head>
<body>
  <div class="wrapper">
      <div class="top-header">
          @if(isset($logoPath) && file_exists($logoPath))
              <img src="{{ $message->embed($logoPath) }}" alt="Logo Participar Para Transformar">
          @endif

          <div class="quote-box">
              "Precisamos contribuir para criar a escola que é aventura, que marcha, que não tem medo do risco. A escola em que se pensa, em que se cria, em que se fala, em que se adivinha, a escola que, apaixonadamente, diz sim à vida".
              <div class="quote-author">Paulo Freire</div>
          </div>
      </div>
      <div class="content">
          <p class="card-title">Olá, {{ $nome }}!</p>
          <p class="card-text">Desejamos que esteja tudo bem com você.</p>

          <p class="card-text">
              Segue anexo o certificado do curso Participar para Transformar. Verifique todos os seus dados, por gentileza, e caso haja algum dado errado, basta nos responder informando e indicando o dado correto, ok?
          </p>

          <p class="card-text">
              Foi uma grande alegria contar com a sua participação nesse curso.
          </p>
          <p class="card-text">
              Agradecemos pela confiança no trabalho do Instituto Paulo Freire e esperamos contar com a sua participação em outras oportunidades.
          </p>
          <p class="card-text">
              Sigamos juntos/as/es, fortalecendo a Escola Cidadã, a educação pública, popular, democrática, que promove a educação de qualidade social.
          </p>

          <div class="signature">
              <p style="margin-bottom: 4px;">Grande abraço,</p>
              <strong>#EquipeEaDFreiriana</strong><br>
              Instituto Paulo Freire
          </div>

          <div class="footer-banner">
              @if(isset($bannerPath) && file_exists($bannerPath))
                  <img src="{{ $message->embed($bannerPath) }}" alt="Banner Institucional">
              @endif
          </div>
      </div>
  </div>
</body>
</html>
