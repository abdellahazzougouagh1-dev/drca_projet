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
        $name = !empty($notifiable->name) ? ' ' . $notifiable->name : '';

        return (new MailMessage)
            ->subject('🔐 Code de vérification de mot de passe — ONCA')
            ->greeting('Bonjour' . $name . ',')
            ->line('Vous avez demandé la modification ou réinitialisation de votre mot de passe sur la plateforme DRCA.')
            ->line('Voici votre code de sécurité à 6 chiffres :')
            ->line('👉 ** ' . $this->code . ' **')
            ->line('⏳ Ce code est valable pendant 10 minutes et ne peut être utilisé qu’une seule fois.')
            ->line('Si vous n’avez pas demandé ce code, ignorez cet email. Ne communiquez jamais ce code à un tiers.')
            ->salutation('Direction Régionale de Coordination de l’Agriculture — ONCA');
    }
}
