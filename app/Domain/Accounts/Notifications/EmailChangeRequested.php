<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso enviado ao endereco ANTIGO. E a rede de seguranca contra tomada de
 * conta: mesmo que alguem tenha acesso a sessao, o titular real e informado.
 */
class EmailChangeRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $newEmail) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $masked = preg_replace('/^(.).*(@.*)$/u', '$1***$2', $this->newEmail);

        return (new MailMessage)
            ->subject('Foi pedida a alteração do email da tua conta')
            ->line('Foi pedida a alteração do email da tua conta queixa.me para '.$masked.'.')
            ->line('Se foste tu, confirma no email novo. Nada muda até essa confirmação.')
            ->action('Cancelar o pedido', route('consumer.profile.edit'))
            ->line('Se não foste tu, cancela o pedido e altera a tua palavra-passe imediatamente.');
    }
}
