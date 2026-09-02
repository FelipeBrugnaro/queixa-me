<?php

declare(strict_types=1);

namespace App\Domain\Accounts\Notifications;

use App\Domain\Accounts\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class ConfirmEmailChange extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $user,
        private readonly string $token,
        private readonly Carbon $expiresAt,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirma o teu novo email no queixa.me')
            ->greeting('Olá'.($this->user->first_name ? ', '.$this->user->first_name : '').'!')
            ->line('Recebemos um pedido para passares a usar este endereço na tua conta queixa.me.')
            ->action('Confirmar novo email', route('consumer.profile.email.confirm', $this->token))
            ->line('Este link expira '.$this->expiresAt->diffForHumans().'.')
            ->line('Se não foste tu, ignora esta mensagem: o teu email atual continua ativo.');
    }
}
