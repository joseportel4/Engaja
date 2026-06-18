<?php

namespace App\Mail;

use App\Models\Agendamento;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgendamentoCriadoMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ?string $logoData = null;

    public function __construct(public Agendamento $agendamento) {}

    public function build(): self
    {
        $logoPath = public_path('images/engaja-bg-white.png');
        if (file_exists($logoPath)) {
            $data = base64_encode(file_get_contents($logoPath));
            $this->logoData = 'data:image/png;base64,'.$data;
        }

        $nomeAcao = $this->agendamento->atividadeAcao?->nome ?? 'Agendamento';

        return $this->subject('Novo agendamento criado - '.$nomeAcao)
            ->view('emails.agendamentos.criado')
            ->with(['logoData' => $this->logoData]);
    }
}
