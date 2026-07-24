<?php

namespace App\Notifications\Cartas;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CadastroRealizadoComSucessoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Seu cadastro no Cartas para Esperançar está confirmado')
            ->view('emails.cartas.cadastro-sucesso', [
                'userName' => $notifiable->name,
            ]);
    }
}
