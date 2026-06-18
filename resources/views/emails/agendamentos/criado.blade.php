@php
  $url = route('agendamentos.efetivacoes.index');
  $agendamento = $agendamento ?? null;
  $atividadeAcao = $agendamento?->atividadeAcao;
  $municipio = $agendamento?->municipio;
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
    .card {
      background: #fff;
      border-radius: 12px;
      padding: 28px 28px 24px 28px;
      box-shadow: 0 14px 35px rgba(0,0,0,0.06);
      text-align: left;
    }
    h1 {
      font-size: 22px;
      color: #1f2937;
      margin: 0 0 12px 0;
      font-weight: 700;
    }
    p {
      color: #374151;
      font-size: 15px;
      line-height: 1.6;
      margin: 0 0 14px 0;
    }
    .info-table {
      width: 100%;
      border-collapse: collapse;
      margin: 0 0 20px 0;
      font-size: 14px;
    }
    .info-table td {
      padding: 6px 8px;
      color: #374151;
      vertical-align: top;
    }
    .info-table td:first-child {
      font-weight: 700;
      white-space: nowrap;
      width: 40%;
      color: #1f2937;
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
      @if(!empty($logoData))
        <img src="{{ $logoData }}" alt="Engaja" style="height:48px;">
      @endif
    </div>
    <div class="card">
      <h1>Novo agendamento criado</h1>
      <p>Um novo agendamento foi registrado no sistema e está aguardando efetivação.</p>

      @if($agendamento)
      <table class="info-table">
        @if($atividadeAcao)
        <tr>
          <td>Ação/Atividade:</td>
          <td>{{ $atividadeAcao->nome }}</td>
        </tr>
        @endif
        <tr>
          <td>Data e horário:</td>
          <td>{{ $agendamento->data_horario?->format('d/m/Y H:i') }}</td>
        </tr>
        @if($municipio)
        <tr>
          <td>Município:</td>
          <td>{{ $municipio->nome }}</td>
        </tr>
        @endif
        <tr>
          <td>Local:</td>
          <td>{{ $agendamento->local_acao }}</td>
        </tr>
        <tr>
          <td>Público participante:</td>
          <td>{{ $agendamento->publico_participante }}</td>
        </tr>
        @if($agendamento->turma)
        <tr>
          <td>Turma:</td>
          <td>{{ $agendamento->turma }}</td>
        </tr>
        @endif
      </table>
      @endif

      <p>
        <a class="btn" href="{{ $url }}">Ver agendamentos pendentes</a>
      </p>
      <p class="muted">Se o botão não funcionar, copie e cole este link no navegador:<br>{{ $url }}</p>
      <p class="muted">Esta é uma mensagem automática. Não responda este e-mail.</p>
    </div>
  </div>
</body>
</html>
