<?php

namespace App\Notifications\Cartas;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class CartasVerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme seu e-mail - Cartas para Esperançar')
            ->view('emails.cartas.verify-email', [
                'userName' => $notifiable->name,
                'url' => $url,
            ]);
    }
}
