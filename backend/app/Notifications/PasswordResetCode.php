<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetCode extends Notification
{
    public function __construct(public string $code) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre code de réinitialisation — ONCA')
            ->greeting('Bonjour,')
            ->line('Votre code de réinitialisation du mot de passe :')
            ->line($this->code)
            ->line('Ce code est valable 10 minutes et ne peut être utilisé qu’une fois.')
            ->line('Si vous n’avez pas demandé ce code, ignorez cet email. Ne partagez pas ce code.');
    }
}
