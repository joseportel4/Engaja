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
      background: #fef3c7;
      color: #92400e;
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
    .parecer-box {
      background: #fffbeb;
      border: 1px solid #fcd34d;
      border-radius: 8px;
      padding: 14px 16px;
      margin: 0 0 20px;
      font-size: 14px;
      color: #78350f;
      line-height: 1.6;
    }
    .parecer-box strong {
      display: block;
      margin-bottom: 6px;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #92400e;
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
        <span class="label">Ajuste solicitado</span>
        <h1>Sua resposta precisa de um ajuste</h1>
      </div>

      <p>Olá, {{ $voluntarioNome }}.</p>

      <p>
        A equipe de gestão revisou sua resposta e identificou um ponto que precisa ser corrigido
        antes de ser enviado ao educando.
      </p>

      @if($parecerVerificacao)
        <div class="parecer-box">
          <strong>Observação da gestão</strong>
          {{ $parecerVerificacao }}
        </div>
      @endif

      <p>
        Acesse a conversa, leia o feedback acima e envie a versão corrigida da sua carta.
      </p>

      <p>
        <a class="btn" href="{{ $url }}">Acessar a conversa</a>
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
