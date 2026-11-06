<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TemporaryPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $password)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu contraseña temporal de HLazcano')
            ->greeting('Hola, '.$notifiable->nombre)
            ->line('Se solicitó una contraseña temporal para tu cuenta.')
            ->line('Contraseña temporal: '.$this->password)
            ->line('Por seguridad, el sistema te pedirá cambiarla al iniciar sesión.');
    }
}