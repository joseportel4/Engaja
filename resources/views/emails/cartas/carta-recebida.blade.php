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
    }
    .wrapper {
      max-width: 620px;
      margin: 0 auto;
      padding: 32px 16px;
    }
    .logo-area {
      text-align: center;
      margin-bottom: 20px;
    }
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 32px 32px 28px;
      box-shadow: 0 14px 35px rgba(0,0,0,0.06);
    }
    .card-header {
      border-bottom: 1px solid #f0ece8;
      padding-bottom: 18px;
      margin-bottom: 22px;
    }
    .label {
      display: inline-block;
      background: #fff3e0;
      color: #b45309;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 4px 10px;
      border-radius: 999px;
      margin-bottom: 12px;
    }
    h1 {
      font-size: 21px;
      color: #1f2937;
      margin: 0;
      font-weight: 700;
      line-height: 1.3;
    }
    p {
      color: #374151;
      font-size: 15px;
      line-height: 1.65;
      margin: 0 0 16px;
    }
    .btn {
      display: inline-block;
      background: #7c3aed;
      color: #fff !important;
      padding: 13px 28px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 700;
      font-size: 15px;
      margin: 8px 0 20px;
    }
    .muted {
      font-size: 13px;
      color: #6b7280;
      word-break: break-all;
      margin-top: 0;
    }
    .footer {
      text-align: center;
      margin-top: 24px;
      font-size: 12px;
      color: #9ca3af;
    }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo-area">
      <img src="{{ asset('images/cartas/cartas-logo.png') }}" alt="Cartas para Esperançar" style="max-height:60px;width:auto;">
    </div>

    <div class="card">
      <div class="card-header">
        <span class="label">Nova carta</span>
        <h1>
          @if($isPrimeiraVez)
            Você recebeu uma carta de {{ $remetenteNome }}
          @else
            {{ $remetenteNome }} respondeu à sua carta
          @endif
        </h1>
      </div>

      <p>Olá, {{ $voluntarioNome }}.</p>

      @if($isPrimeiraVez)
        <p>
          Uma nova carta chegou para você no <strong>Cartas para Esperançar</strong>.
          Acesse a plataforma para ler a mensagem e enviar sua resposta quando estiver pronto(a).
        </p>
      @else
        <p>
          <strong>{{ $remetenteNome }}</strong> enviou uma nova carta na conversa de vocês.
          Acesse a plataforma para continuar essa troca de mensagens.
        </p>
      @endif

      <p>
        <a class="btn" href="{{ $url }}">Ver a carta</a>
      </p>
      <p class="muted">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $url }}</p>
      <p class="muted">Esta é uma mensagem automática. Não responda este e-mail.</p>
    </div>

    <div class="footer">
      Projeto ALFA-EJA Brasil · Cartas para Esperançar
    </div>
  </div>
</body>
</html>
