<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Письмо со ссылкой подтверждения почты (текст на русском).
 * Подписанная ссылка живёт 60 минут (auth.verification.expire).
 */
class VerifyEmailCustom extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Подтвердите адрес электронной почты')
            ->greeting('Здравствуйте!')
            ->line('Вы зарегистрировались на маркетплейсе. Чтобы открыть кабинет продавца, подтвердите адрес почты.')
            ->action('Подтвердить почту', $url)
            ->line('Ссылка действует 60 минут. Если срок истёк, запросите новое письмо на странице подтверждения.')
            ->line('Если вы не регистрировались, просто проигнорируйте это письмо.');
    }
}
