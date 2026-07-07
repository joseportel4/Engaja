<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CartasResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('cartas.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        return (new MailMessage)
            ->subject('Recuperação de senha - Cartas para Esperançar')
            ->line('Recebemos uma solicitação de recuperação de senha para sua conta.')
            ->action('Redefinir senha', $url)
            ->line('Se você não solicitou a recuperação, nenhuma ação é necessária.');
    }
}
