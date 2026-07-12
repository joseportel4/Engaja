<?php

namespace App\Notifications\Cartas;

use App\Models\Cartas\Carta;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CartaRecebidaNotification extends Notification implements ShouldQueue
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

        $remetenteNome = $this->carta->educando?->user?->name ?? 'um educando';

        $primeiraMensagem = $this->carta->mensagens->first();
        $isPrimeiraVez = $primeiraMensagem?->rodada === 1;

        return (new MailMessage)
            ->subject('Você recebeu uma carta — Cartas para Esperançar')
            ->view('emails.cartas.carta-recebida', [
                'voluntarioNome' => $notifiable->name,
                'remetenteNome'  => $remetenteNome,
                'isPrimeiraVez'  => $isPrimeiraVez,
                'url'            => $url,
            ]);
    }
}
