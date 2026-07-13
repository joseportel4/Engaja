<?php

namespace App\Notifications\Cartas;

use App\Models\Cartas\Carta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CartaAguardandoVerificacaoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Carta $carta) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('cartas.cartas.show', $this->carta);

        $voluntarioNome = $this->carta->voluntario?->name ?? 'Um voluntário';
        $educandoNome   = $this->carta->educando?->user?->name ?? 'um educando';

        return (new MailMessage)
            ->subject('Carta aguardando verificação — Cartas para Esperançar')
            ->view('emails.cartas.carta-aguardando-verificacao', [
                'gestorNome'     => $notifiable->name,
                'voluntarioNome' => $voluntarioNome,
                'educandoNome'   => $educandoNome,
                'codigoCarta'    => $this->carta->codigo,
                'url'            => $url,
            ]);
    }
}
